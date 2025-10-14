#!/usr/bin/env python3
"""
Character Substitution Tool for OCR Correction

Uses character_mapping.json to apply common OCR error corrections
to alliance R5 names extracted from screenshots.

Phase 1 of OCR Training Pipeline
"""
import json
import re
from pathlib import Path
from typing import Dict, List, Tuple, Optional

class CharacterSubstitutor:
    """Applies character substitutions based on OCR error patterns"""

    def __init__(self, mapping_file: Optional[Path] = None):
        """
        Initialize with character mapping file

        Args:
            mapping_file: Path to character_mapping.json (defaults to same directory)
        """
        if mapping_file is None:
            mapping_file = Path(__file__).parent / "character_mapping.json"

        with open(mapping_file, 'r', encoding='utf-8') as f:
            self.mapping = json.load(f)

        # Build reverse lookup: misread -> correct
        self.reverse_map = self._build_reverse_map()

    def _build_reverse_map(self) -> Dict[str, List[str]]:
        """Build reverse mapping from common misreads to correct characters"""
        reverse = {}

        for item in self.mapping['common_substitutions']['mappings']:
            correct = item['correct']
            for misread in item['common_misreads']:
                if misread not in reverse:
                    reverse[misread] = []
                reverse[misread].append({
                    'correct': correct,
                    'type': item['character_type'],
                    'confidence': 'low' if len(item['common_misreads']) > 3 else 'medium'
                })

        return reverse

    def correct_text(self, text: str, alliance_tag: Optional[str] = None) -> Tuple[str, List[Dict]]:
        """
        Apply character substitutions to text

        Args:
            text: OCR output text to correct
            alliance_tag: Alliance tag for context-based corrections

        Returns:
            Tuple of (corrected_text, list of corrections made)
        """
        corrections = []
        result = text

        # Apply common substitutions
        for item in self.mapping['common_substitutions']['mappings']:
            correct = item['correct']
            for misread in item['common_misreads']:
                # Only replace if misread is non-empty and actually exists in text
                if misread and misread in result:
                    count = result.count(misread)
                    result = result.replace(misread, correct)
                    corrections.append({
                        'original': misread,
                        'corrected': correct,
                        'type': item['character_type'],
                        'position': 'unknown',
                        'count': count
                    })

        # Apply context-based corrections if alliance tag provided
        if alliance_tag:
            context_corrections = self._apply_context_rules(result, alliance_tag)
            corrections.extend(context_corrections)

        return result, corrections

    def _apply_context_rules(self, text: str, alliance_tag: str) -> List[Dict]:
        """Apply alliance-specific context rules"""
        corrections = []

        for rule in self.mapping['context_rules']['rules']:
            if rule['alliance_tag'] == alliance_tag:
                # Check if text matches expected pattern
                expected = rule['expected_pattern']

                if 'korean' in expected and not self._contains_korean(text):
                    corrections.append({
                        'issue': 'missing_korean',
                        'expected': expected,
                        'confidence': 'low',
                        'suggestion': rule['correction']
                    })

                if 'emoji' in expected and not self._contains_special_chars(text):
                    corrections.append({
                        'issue': 'missing_emoji',
                        'expected': expected,
                        'confidence': 'low',
                        'suggestion': rule['correction']
                    })

        return corrections

    def _contains_korean(self, text: str) -> bool:
        """Check if text contains Korean characters"""
        korean_pattern = re.compile(r'[\uac00-\ud7a3]')
        return bool(korean_pattern.search(text))

    def _contains_special_chars(self, text: str) -> bool:
        """Check if text contains special Unicode characters"""
        special_chars = set()
        for item in self.mapping['common_substitutions']['mappings']:
            if item['character_type'] in ['special_symbol', 'emoji_composite', 'special_bracket']:
                special_chars.add(item['correct'])

        return any(char in text for char in special_chars)

    def check_confidence(self, text: str, alliance_tag: Optional[str] = None) -> Dict:
        """
        Evaluate confidence in OCR result

        Args:
            text: OCR output text
            alliance_tag: Alliance tag for context

        Returns:
            Dictionary with confidence score and reasons
        """
        confidence_data = {
            'score': 1.0,  # Start at 100%
            'level': 'high',
            'issues': [],
            'signals': []
        }

        # Check low confidence signals
        low_signals = self.mapping['confidence_indicators']['low_confidence_signals']
        for signal in low_signals:
            if self._matches_signal(text, signal, alliance_tag):
                confidence_data['issues'].append(signal)
                confidence_data['score'] -= 0.2

        # Check high confidence signals
        high_signals = self.mapping['confidence_indicators']['high_confidence_signals']
        for signal in high_signals:
            if self._matches_signal(text, signal, alliance_tag):
                confidence_data['signals'].append(signal)

        # Determine level
        if confidence_data['score'] >= 0.8:
            confidence_data['level'] = 'high'
        elif confidence_data['score'] >= 0.5:
            confidence_data['level'] = 'medium'
        else:
            confidence_data['level'] = 'low'

        return confidence_data

    def _matches_signal(self, text: str, signal: str, alliance_tag: Optional[str]) -> bool:
        """Check if text matches a confidence signal"""
        signal_lower = signal.lower()

        if 'ascii' in signal_lower and 'korean' in signal_lower:
            return not self._contains_korean(text) and alliance_tag in ['UvvU', 'NKOT', '1985']

        if 'special characters' in signal_lower:
            return not self._contains_special_chars(text)

        if 'length' in signal_lower:
            return len(text) < 3 or len(text) > 30

        if 'artifacts' in signal_lower:
            artifacts = ['|||', '^^^', '```', '...']
            return any(art in text for art in artifacts)

        return False

    def get_engine_recommendation(self, alliance_tag: str) -> str:
        """
        Recommend best OCR engine based on alliance tag

        Args:
            alliance_tag: Alliance tag to analyze

        Returns:
            Recommended engine name
        """
        # Check if alliance is in context rules
        for rule in self.mapping['context_rules']['rules']:
            if rule['alliance_tag'] == alliance_tag:
                pattern = rule['expected_pattern']

                if 'korean' in pattern:
                    return 'easyocr'  # Best for Korean
                elif 'special' in pattern or 'emoji' in pattern:
                    return 'paddleocr'  # Best for Unicode
                else:
                    return 'tesseract'  # Best for ASCII

        return 'tesseract'  # Default


def main():
    """Example usage and testing"""
    import sys
    import io
    # Fix Unicode output on Windows
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

    substitutor = CharacterSubstitutor()

    # Test cases from training data
    test_cases = [
        ("ULoveGucciio", "STR8"),  # ღ misread as o
        ("3Luna3", "86KO"),  # ʚᴸᵘᶰᵃɞ misread as 3Luna3
        ("쿠치나", "UvvU"),  # Missing emoji
        ("Noni", "NKOT"),  # Missing Korean part
    ]

    print("Character Substitution Tool - Test Results\n")
    print("=" * 70)

    for text, tag in test_cases:
        print(f"\nAlliance: {tag}")
        print(f"OCR Output: {text}")

        # Apply corrections
        corrected, corrections = substitutor.correct_text(text, tag)
        print(f"Corrected:  {corrected}")

        if corrections:
            print("Corrections made:")
            for corr in corrections:
                print(f"  - {corr}")

        # Check confidence
        confidence = substitutor.check_confidence(text, tag)
        print(f"Confidence: {confidence['level'].upper()} ({confidence['score']:.1%})")
        if confidence['issues']:
            print(f"Issues: {', '.join(confidence['issues'])}")

        # Get engine recommendation
        engine = substitutor.get_engine_recommendation(tag)
        print(f"Recommended engine: {engine}")

        print("-" * 70)


if __name__ == '__main__':
    main()
