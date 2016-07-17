### Simple PHP class to provide a seekable video stream intermediary

Purpose of this PHP class is to mediate the provision of a video stream w/ seek capability.

#### Constructor:
```php
void SeekableVideo($file[, $mime_type], [$output_filename]]);
```
* `$file`: path to the source video file
* `$mime_type` (optional): mime type to output in HTTP headers. If not specified, tries to presume from file extension. If it is not possible, throws an error
* `$output_filename` (optional): file name to output in HTTP headers. If not specified, uses source file name

#### Public variables:
* `$video->mime_type`: set output video mime type
* `$video->filename`: set output video filename

#### Public functions:
* `$video->begin_stream()`: begins video file stream

#### Usage example:

```php
require_once('SeekableVideo.php');

/*
 * Common use of a video intermediary: make sure user is allowed to access this video.
 * <Code here necessary checks>
 */

$video = new SeekableVideo('example.mp4');

$video->mime_type = 'video/mp4';
$video->output_filename = 'MyVideo.mp4';

$video->begin_stream();
```
