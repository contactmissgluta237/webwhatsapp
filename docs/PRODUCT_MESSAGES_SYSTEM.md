# WhatsApp Product Messages System

## Overview

This system allows WhatsApp AI responses to include product information with media attachments that are stored and displayed separately from the main AI response.

## Architecture

### Database Schema

The system extends the existing `whatsapp_messages` table with two new columns:

- `media_urls` (JSON): Stores an array of media URLs for product messages
- `message_subtype` (ENUM): Distinguishes between 'main' and 'product' message types

### Key Components

#### 1. DTOs (Data Transfer Objects)
- `ProductDataDTO`: Encapsulates product information and media URLs
- `WhatsAppMessageResponseDTO`: Extended to support products array
- `WhatsAppMessageRequestDTO`: Handles incoming messages

#### 2. Repository Pattern
- `WhatsAppMessageRepositoryInterface`: Defines message storage contracts
- `EloquentWhatsAppMessageRepository`: Implements storage logic with product support

#### 3. Media Type Detection
- `MediaTypeDetector`: Service class for identifying media types (image, video, audio, document)

#### 4. View System
- Enhanced conversation display with media grid support
- Responsive media display with type-specific rendering

## Usage

### Storing Product Messages

```php
use App\DTOs\WhatsApp\ProductDataDTO;
use App\DTOs\WhatsApp\WhatsAppMessageResponseDTO;

$products = [
    new ProductDataDTO(
        formattedProductMessage: "🛍️ *iPhone 15 Pro*\n\n💰 **850,000 XAF**\n\n📝 Description...",
        mediaUrls: [
            'https://example.com/image1.jpg',
            'https://example.com/image2.jpg'
        ]
    )
];

$aiResponse = WhatsAppMessageResponseDTO::success(
    aiResponse: 'Main AI response text',
    aiDetails: $aiDetails,
    products: $products
);

// This will automatically store both main message and product messages
$repository->storeMessageExchange($account, $incomingMessage, $aiResponse);
```

### Media Type Detection

```php
use App\Services\WhatsApp\MediaTypeDetector;

$mediaType = MediaTypeDetector::getMediaType($url);
// Returns: 'image', 'video', 'audio', or 'document'

$isImage = MediaTypeDetector::isImage($url);
$isVideo = MediaTypeDetector::isVideo($url);
$isAudio = MediaTypeDetector::isAudio($url);
```

## Supported Media Types

### Images
- Extensions: jpg, jpeg, png, gif, webp, bmp, svg
- Special URLs: Unsplash, Picsum, URLs containing '/photo-'
- Display: Responsive image grid with fallback icons

### Videos
- Extensions: mp4, mov, avi, wmv, flv, webm, mkv
- Display: HTML5 video player with play overlay

### Audio
- Extensions: mp3, wav, ogg, m4a, aac, flac
- Display: HTML5 audio controls

### Documents
- All other file types
- Display: File icon with extension label

## Database Changes

```sql
ALTER TABLE whatsapp_messages 
ADD COLUMN media_urls JSON NULL COMMENT 'URLs of media for products',
ADD COLUMN message_subtype ENUM('main', 'product') DEFAULT 'main' COMMENT 'Message type';
```

## Model Updates

```php
// WhatsAppMessage model additions
protected $fillable = [
    // ... existing fields
    'media_urls',
    'message_subtype',
];

protected $casts = [
    // ... existing casts
    'media_urls' => 'json',
    'message_subtype' => SpatieEnumCast::class.':'.MessageSubtype::class,
];
```

## Testing

The system includes comprehensive tests:

### Feature Tests
- `ProductMessageStorageTest`: Tests message storage with and without products
- Tests repository functionality and database integration

### Unit Tests
- `MediaTypeDetectorTest`: Tests media type detection logic
- Data-driven tests with multiple URL patterns

Run tests:
```bash
php artisan test tests/Feature/WhatsApp/ProductMessageStorageTest.php
php artisan test tests/Unit/WhatsApp/MediaTypeDetectorTest.php
```

## Frontend Display

The conversation view automatically detects product messages and renders media in a responsive grid:

```php
@if($message->message_subtype && $message->message_subtype->value === 'product' && !empty($message->media_urls))
    <div class="product-media-grid">
        @foreach($message->media_urls as $mediaUrl)
            // Type-specific rendering based on MediaTypeDetector
        @endforeach
    </div>
@endif
```

## Performance Considerations

1. **Media Loading**: Images use lazy loading (`loading="lazy"`)
2. **Error Handling**: Fallback display for failed media loads
3. **Responsive Design**: Grid adapts to different screen sizes
4. **Database**: JSON column for flexible media URL storage

## Future Enhancements

- Media thumbnail generation
- Media file validation
- Batch media processing
- Media caching strategies
- CDN integration support