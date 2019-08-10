### Simple PHP class to provide a seekable video stream intermediary

Purpose of this PHP class is to mediate the provision of a video stream w/ seek capability. It needs PHP >= 7.2

#### Constructor:
```php
void SeekableVideo(string $file[, string $mime_type[, string $output_filename]]);
```
* `$file`: path to the source video file
* `$mime_type` (optional): mime type to output in HTTP headers. If not specified, tries to presume from file extension. If it is not possible, throws a 500 HTTP error
* `$output_filename` (optional): file name to output in HTTP headers. If not specified, source file name is used

#### Public methods:
* `void begin_stream()`: begins video file stream

#### Usage example:

```php
require_once('SeekableVideo.php');

/*
 * Common use of a video intermediary: make sure user is allowed to access this video.
 * <Code necessary checks here>
 */

$video = new SeekableVideo('example.mp4', 'video/mp4', 'MyVideo.mp4');

$video->begin_stream();
```