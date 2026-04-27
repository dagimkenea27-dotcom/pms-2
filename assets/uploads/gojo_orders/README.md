# Gojo Orders Image Storage

This directory stores product images for Gojo Shop orders.

## Storage Details

- **Location**: `assets/uploads/gojo_orders/`
- **Format**: `order_{orderId}_{timestamp}.{extension}`
- **Supported Types**: jpg, jpeg, png, gif, webp
- **Access**: Via `GOJO_UPLOAD_URL` constant

## Automatic Management

- Images are automatically saved when orders are created/updated
- Old images are deleted when replaced with new ones
- Images are deleted when orders are deleted
- Directory is auto-created with 0755 permissions

## Security

- Only images uploaded through the Gojo Shop interface
- Base64 validation before saving
- Unique filenames prevent overwrites
- Not committed to version control (see .gitignore)
