### Simple PHP class to provide a seekable video stream intermediary

Purpose of this PHP class is to intermediate the provision of a video stream w/ seek capability.

#### Usage example:
```php
$video = new SeekableVideo($file[, $mime_type], [$output_filename]]);
```
Public variables:
```php
$video->mime_type = 'video/mp4'; // Set output video mime type
$video->filename = 'OutputVideo.mp4'; // Set output video filename
```

Public functions:
```php
$video->begin_stream();
```
