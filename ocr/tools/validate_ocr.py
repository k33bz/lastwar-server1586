#!/usr/bin/env python3
"""
OCR Validation Test Suite

Tests OCR engines against ground truth training data to measure baseline accuracy.
Generates detailed accuracy reports for character-level and word-level performance.

Phase 1 of OCR Training Pipeline - Baseline Measurement
"""
import json
import sys
import io
from pathlib import Path
from typing import Dict, List, Tuple, Optional
from difflib import SequenceMatcher

# Fix Unicode output on Windows
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

class OCRValidator:
    """Validates OCR engine performance against ground truth data"""

    def __init__(self, training_data_file: Optional[Path] = None, images_dir: Optional[Path] = None):
        """
        Initialize validator with training data

        Args:
            training_data_file: Path to alliance_r5_mapping.json
            images_dir: Path to directory containing training images
        """
        if training_data_file is None:
            training_data_file = Path(__file__).parent.parent / "training_data" / "alliance_r5_mapping.json"

        if images_dir is None:
            images_dir = Path(__file__).parent.parent / "alliance_cards"

        with open(training_data_file, 'r', encoding='utf-8') as f:
            self.training_data = json.load(f)

        self.images_dir = images_dir
        self.results = {
            'total_tests': 0,
            'engines': {},
            'difficulty_breakdown': {
                'easy': {'total': 0, 'correct': 0},
                'medium': {'total': 0, 'correct': 0},
                'hard': {'total': 0, 'correct': 0}
            }
        }

    def calculate_similarity(self, text1: str, text2: str) -> float:
        """
        Calculate character-level similarity between two strings

        Args:
            text1: First string
            text2: Second string

        Returns:
            Similarity ratio (0.0 to 1.0)
        """
        return SequenceMatcher(None, text1, text2).ratio()

    def calculate_character_accuracy(self, ocr_result: str, ground_truth: str) -> Dict:
        """
        Calculate detailed character-level accuracy metrics

        Args:
            ocr_result: OCR engine output
            ground_truth: Manually verified correct text

        Returns:
            Dictionary with accuracy metrics
        """
        similarity = self.calculate_similarity(ocr_result, ground_truth)

        # Calculate edit distance components
        matcher = SequenceMatcher(None, ground_truth, ocr_result)
        correct_chars = 0
        total_chars = len(ground_truth)

        for tag, i1, i2, j1, j2 in matcher.get_opcodes():
            if tag == 'equal':
                correct_chars += (i2 - i1)

        char_accuracy = correct_chars / total_chars if total_chars > 0 else 0.0

        return {
            'similarity': similarity,
            'character_accuracy': char_accuracy,
            'correct_characters': correct_chars,
            'total_characters': total_chars,
            'exact_match': ocr_result == ground_truth
        }

    def test_manual_result(self, alliance_tag: str, ocr_result: str) -> Dict:
        """
        Test a manually provided OCR result against ground truth

        Args:
            alliance_tag: Alliance tag to look up ground truth
            ocr_result: OCR engine output to test

        Returns:
            Test result dictionary
        """
        # Find ground truth
        ground_truth = None
        difficulty = 'unknown'

        for alliance in self.training_data['alliances']:
            if alliance['tag'] == alliance_tag:
                ground_truth = alliance['r5_name']
                difficulty = alliance.get('difficulty', 'unknown')
                break

        if ground_truth is None:
            return {
                'error': f'Alliance tag {alliance_tag} not found in training data',
                'alliance_tag': alliance_tag
            }

        # Calculate accuracy
        accuracy = self.calculate_character_accuracy(ocr_result, ground_truth)

        # Update difficulty breakdown
        if difficulty in self.results['difficulty_breakdown']:
            self.results['difficulty_breakdown'][difficulty]['total'] += 1
            if accuracy['exact_match']:
                self.results['difficulty_breakdown'][difficulty]['correct'] += 1

        return {
            'alliance_tag': alliance_tag,
            'ground_truth': ground_truth,
            'ocr_result': ocr_result,
            'difficulty': difficulty,
            'metrics': accuracy
        }

    def run_test_suite(self, manual_results: List[Tuple[str, str, str]]) -> Dict:
        """
        Run validation test suite on manually provided OCR results

        Args:
            manual_results: List of (engine_name, alliance_tag, ocr_result) tuples

        Returns:
            Complete test results
        """
        self.results['total_tests'] = len(manual_results)

        for engine_name, alliance_tag, ocr_result in manual_results:
            if engine_name not in self.results['engines']:
                self.results['engines'][engine_name] = {
                    'total_tests': 0,
                    'exact_matches': 0,
                    'total_similarity': 0.0,
                    'total_char_accuracy': 0.0,
                    'test_cases': []
                }

            engine_stats = self.results['engines'][engine_name]
            test_result = self.test_manual_result(alliance_tag, ocr_result)

            if 'error' not in test_result:
                engine_stats['total_tests'] += 1
                engine_stats['test_cases'].append(test_result)

                if test_result['metrics']['exact_match']:
                    engine_stats['exact_matches'] += 1

                engine_stats['total_similarity'] += test_result['metrics']['similarity']
                engine_stats['total_char_accuracy'] += test_result['metrics']['character_accuracy']

        return self.results

    def generate_report(self) -> str:
        """
        Generate human-readable accuracy report

        Returns:
            Formatted report string
        """
        report_lines = []
        report_lines.append("=" * 80)
        report_lines.append("OCR Baseline Accuracy Report - Phase 1")
        report_lines.append("=" * 80)
        report_lines.append("")

        # Overall stats
        report_lines.append(f"Total test cases: {self.results['total_tests']}")
        report_lines.append("")

        # Per-engine stats
        for engine_name, stats in self.results['engines'].items():
            if stats['total_tests'] == 0:
                continue

            avg_similarity = stats['total_similarity'] / stats['total_tests']
            avg_char_accuracy = stats['total_char_accuracy'] / stats['total_tests']
            word_accuracy = stats['exact_matches'] / stats['total_tests']

            report_lines.append(f"Engine: {engine_name}")
            report_lines.append("-" * 80)
            report_lines.append(f"  Tests run: {stats['total_tests']}")
            report_lines.append(f"  Word accuracy (exact match): {word_accuracy:.1%} ({stats['exact_matches']}/{stats['total_tests']})")
            report_lines.append(f"  Average character accuracy: {avg_char_accuracy:.1%}")
            report_lines.append(f"  Average similarity score: {avg_similarity:.1%}")
            report_lines.append("")

            # Show failed cases
            failed_cases = [tc for tc in stats['test_cases'] if not tc['metrics']['exact_match']]
            if failed_cases:
                report_lines.append(f"  Failed cases ({len(failed_cases)}):")
                for case in failed_cases[:5]:  # Show first 5 failures
                    report_lines.append(f"    {case['alliance_tag']} ({case['difficulty']})")
                    report_lines.append(f"      Expected: {case['ground_truth']}")
                    report_lines.append(f"      Got:      {case['ocr_result']}")
                    report_lines.append(f"      Accuracy: {case['metrics']['character_accuracy']:.1%}")
                if len(failed_cases) > 5:
                    report_lines.append(f"    ... and {len(failed_cases) - 5} more")
                report_lines.append("")

        # Difficulty breakdown
        report_lines.append("Difficulty Breakdown:")
        report_lines.append("-" * 80)
        for difficulty, stats in self.results['difficulty_breakdown'].items():
            if stats['total'] > 0:
                accuracy = stats['correct'] / stats['total']
                report_lines.append(f"  {difficulty.capitalize()}: {accuracy:.1%} ({stats['correct']}/{stats['total']})")
        report_lines.append("")

        report_lines.append("=" * 80)

        return "\n".join(report_lines)

    def save_report(self, output_file: Path):
        """
        Save detailed JSON report to file

        Args:
            output_file: Path to save JSON report
        """
        # Calculate summary statistics
        summary = {
            'total_tests': self.results['total_tests'],
            'engines': {}
        }

        for engine_name, stats in self.results['engines'].items():
            if stats['total_tests'] == 0:
                continue

            summary['engines'][engine_name] = {
                'total_tests': stats['total_tests'],
                'exact_matches': stats['exact_matches'],
                'word_accuracy': stats['exact_matches'] / stats['total_tests'],
                'average_character_accuracy': stats['total_char_accuracy'] / stats['total_tests'],
                'average_similarity': stats['total_similarity'] / stats['total_tests']
            }

        summary['difficulty_breakdown'] = {}
        for difficulty, stats in self.results['difficulty_breakdown'].items():
            if stats['total'] > 0:
                summary['difficulty_breakdown'][difficulty] = {
                    'total': stats['total'],
                    'correct': stats['correct'],
                    'accuracy': stats['correct'] / stats['total']
                }

        output = {
            'metadata': {
                'phase': 1,
                'purpose': 'Baseline OCR accuracy measurement',
                'training_examples': self.results['total_tests']
            },
            'summary': summary,
            'detailed_results': self.results
        }

        with open(output_file, 'w', encoding='utf-8') as f:
            json.dump(output, f, indent=2, ensure_ascii=False)


def main():
    """Example usage with simulated OCR results"""

    validator = OCRValidator()

    # Simulated OCR results for demonstration
    # In practice, you would run actual OCR engines on the training images
    # Format: (engine_name, alliance_tag, ocr_result)
    manual_test_results = [
        # Tesseract results (simulated - tends to fail on special characters)
        ('tesseract', 'ORCE', 'EchoJT'),  # Should pass
        ('tesseract', 'UvvU', '쿠치나'),  # Missing emoji
        ('tesseract', 'NKOT', 'Noni'),  # Missing Korean part
        ('tesseract', 'STR8', 'ULoveGucciio'),  # ღ → o
        ('tesseract', 'EPIC', 'MastaGinger'),  # Should pass
        ('tesseract', '86KO', '3Luna3'),  # Completely wrong

        # EasyOCR results (simulated - better with Korean)
        ('easyocr', 'ORCE', 'EchoJT'),  # Should pass
        ('easyocr', 'UvvU', '쿠치나'),  # Missing emoji
        ('easyocr', 'NKOT', '잔인한노니 Noni'),  # Should pass!
        ('easyocr', 'STR8', 'ULoveGucciig'),  # ღ → g
        ('easyocr', '86KO', 'eLunae'),  # Wrong but closer
        ('easyocr', '1985', '최사령 관'),  # Should pass
    ]

    # Run test suite
    results = validator.run_test_suite(manual_test_results)

    # Print report
    print(validator.generate_report())

    # Save detailed JSON report
    report_path = Path(__file__).parent.parent / "reports" / "baseline_accuracy.json"
    report_path.parent.mkdir(exist_ok=True)
    validator.save_report(report_path)
    print(f"\nDetailed report saved to: {report_path}")


if __name__ == '__main__':
    main()
