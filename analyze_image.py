from PIL import Image

# Open the image
img = Image.open('images/2025-10-06_14-30-19.png')
width, height = img.size

print(f"Image dimensions: {width}x{height}")
print(f"\nAnalyzing image structure...")

# Sample some pixel colors to understand the layout
print(f"\nPixel at (50, 100): {img.getpixel((50, 100))}")
print(f"Pixel at (50, 150): {img.getpixel((50, 150))}")

# Let's save a test crop to verify positioning
test_crop = img.crop((30, 90, 70, 130))
test_crop.save('images/test_crop.png')
print(f"\nTest crop saved to images/test_crop.png")
print("Please check this to see if it captured a logo correctly")
