<?php declare(strict_types=1);

namespace Suna\Utils;

use Suna\Exceptions\ImageException;
use Suna\Exceptions\InvalidArgumentException;
use Suna\Exceptions\NotSupportedException;
use Suna\Exceptions\UnknownImageFileException;
use Suna\Traits\TrObject;

/**
 * Image Helpers
 *
 * @version 0.1
 * @author Web
 */
class Image
{
    use TrObject;

    public const SHRINK_ONLY = 0b0001;

    public const STRETCH = 0b0010;

    public const FIT = 0b0000;

    public const FILL = 0b0100;

    public const EXACT = 0b1000;

    public const
        JPEG = IMAGETYPE_JPEG,
        PNG = IMAGETYPE_PNG,
        GIF = IMAGETYPE_GIF,
        WEBP = IMAGETYPE_WEBP,
        BMP = IMAGETYPE_BMP;

    public const EMPTY_GIF = "GIF89a\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\x00\x00\x00!\xf9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;";

    private const FORMATS = [self::JPEG => 'jpeg', self::PNG => 'png', self::GIF => 'gif', self::WEBP => 'webp', self::BMP => 'bmp'];

    private \GdImage $image;


    /**
     * @param int $red
     * @param int $green
     * @param int $blue
     * @param int $transparency
     * @return array
     */
    public static function rgb(int $red, int $green, int $blue, int $transparency = 0): array
    {
        return [
            'red' => max(0, min(255, $red)),
            'green' => max(0, min(255, $green)),
            'blue' => max(0, min(255, $blue)),
            'alpha' => max(0, min(127, $transparency)),
        ];
    }

    /**
     * @param string $file
     * @param int|null $type
     * @return static
     */
    public static function fromFile(string $file, int &$type = null): static
    {
        if (!extension_loaded('gd')) {
            throw new NotSupportedException('PHP extension GD is not loaded.');
        }

        $type = self::detectTypeFromFile($file);
        if (!$type) {
            throw new UnknownImageFileException(is_file($file) ? "Unknown type of file '$file'." : "File '$file' not found.");
        }

        $method = 'imagecreatefrom' . self::FORMATS[$type];
        return new static(Callback::invokeSafe($method, [$file], function (string $message): void {
            throw new ImageException($message);
        }));
    }

    /**
     * @param string $s
     * @param int|null $type
     * @return static
     * @throws \Suna\Exceptions\UnknownImageFileException
     */
    public static function fromString(string $s, int &$type = null): static
    {
        if (!extension_loaded('gd')) {
            throw new NotSupportedException('PHP extension GD is not loaded.');
        }

        $type = self::detectTypeFromString($s);
        if (!$type) {
            throw new UnknownImageFileException('Unknown type of image.');
        }

        return new static(Callback::invokeSafe('imagecreatefromstring', [$s], function (string $message): void {
            throw new ImageException($message);
        }));
    }


    /**
     * @param int $width
     * @param int $height
     * @param array|null $color
     * @return static
     */
    public static function fromBlank(int $width, int $height, array $color = null): static
    {
        if (!extension_loaded('gd')) {
            throw new NotSupportedException('PHP extension GD is not loaded.');
        }

        if ($width < 1 || $height < 1) {
            throw new InvalidArgumentException('Image width and height must be greater than zero.');
        }

        $image = imagecreatetruecolor($width, $height);
        if ($color) {
            $color += ['alpha' => 0];
            $color = imagecolorresolvealpha($image, $color['red'], $color['green'], $color['blue'], $color['alpha']);
            imagealphablending($image, false);
            imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, $color);
            imagealphablending($image, true);
        }
        return new static($image);
    }

    /**
     * @param string $file
     * @return int|null
     */
    public static function detectTypeFromFile(string $file): ?int
    {
        $type = @getimagesize($file)[2]; // @ - files smaller than 12 bytes causes read error
        return isset(self::FORMATS[$type]) ? $type : null;
    }

    /**
     * @param string $s
     * @return int|null
     */
    public static function detectTypeFromString(string $s): ?int
    {
        $type = @getimagesizefromstring($s)[2]; // @ - strings smaller than 12 bytes causes read error
        return isset(self::FORMATS[$type]) ? $type : null;
    }

    /**
     * @param int $type
     * @return string
     */
    public static function typeToExtension(int $type): string
    {
        if (!isset(self::FORMATS[$type])) {
            throw new InvalidArgumentException("Unsupported image type '$type'.");
        }
        return self::FORMATS[$type];
    }

    /**
     * @param int $type
     * @return string
     */
    public static function typeToMimeType(int $type): string
    {
        return 'image/' . self::typeToExtension($type);
    }

    /**
     * @param \GdImage $image
     */
    public function __construct(\GdImage $image)
    {
        $this->setImageResource($image);
        imagesavealpha($image, true);
    }

    /**
     * @return int
     */
    public function getWidth(): int
    {
        return imagesx($this->image);
    }

    /**
     * @return int
     */
    public function getHeight(): int
    {
        return imagesy($this->image);
    }

    /**
     * @param \GdImage $image
     * @return $this
     */
    protected function setImageResource(\GdImage $image): static
    {
        $this->image = $image;
        return $this;
    }

    /**
     * @return \GdImage
     */
    public function getImageResource(): \GdImage
    {
        return $this->image;
    }

    /**
     * @param int|string|null $width
     * @param int|string|null $height
     * @param int $flags
     * @return $this
     */
    public function resize(int|string|null $width, int|string|null $height, int $flags = self::FIT): static
    {
        if ($flags & self::EXACT) {
            return $this->resize($width, $height, self::FILL)->crop('50%', '50%', $width, $height);
        }

        [$newWidth, $newHeight] = static::calculateSize($this->getWidth(), $this->getHeight(), $width, $height, $flags);

        if ($newWidth !== $this->getWidth() || $newHeight !== $this->getHeight()) { // resize
            $newImage = static::fromBlank($newWidth, $newHeight, self::rgb(0, 0, 0, 127))->getImageResource();
            imagecopyresampled($newImage, $this->image, 0, 0, 0, 0, $newWidth, $newHeight, $this->getWidth(), $this->getHeight());
            $this->image = $newImage;
        }

        if ($width < 0 || $height < 0) {
            imageflip($this->image, $width < 0 ? ($height < 0 ? IMG_FLIP_BOTH : IMG_FLIP_HORIZONTAL) : IMG_FLIP_VERTICAL);
        }
        return $this;
    }

    /**
     * @param int $srcWidth
     * @param int $srcHeight
     * @param $newWidth
     * @param $newHeight
     * @param int $flags
     * @return array
     */
    public static function calculateSize(int $srcWidth, int $srcHeight, $newWidth, $newHeight, int $flags = self::FIT): array {
        $newWidth = abs($newWidth);
        if (self::isPercent($newWidth)) {
            $newWidth = (int) round($srcWidth / 100 * abs($newWidth));
            $percents = true;
        }

        $newHeight = abs($newHeight);
        if (self::isPercent($newHeight)) {
            $newHeight = (int) round($srcHeight / 100 * abs($newHeight));
            $flags |= empty($percents) ? 0 : self::STRETCH;
        }

        if ($flags & self::STRETCH) {
            if (!$newWidth || !$newHeight) {
                throw new InvalidArgumentException('For stretching must be both width and height specified.');
            }

            if ($flags & self::SHRINK_ONLY) {
                $newWidth = (int) round($srcWidth * min(1, $newWidth / $srcWidth));
                $newHeight = (int) round($srcHeight * min(1, $newHeight / $srcHeight));
            }

        } else {
            if (!$newWidth && !$newHeight) {
                throw new InvalidArgumentException('At least width or height must be specified.');
            }

            $scale = [];
            if ($newWidth > 0) { // fit width
                $scale[] = $newWidth / $srcWidth;
            }

            if ($newHeight > 0) { // fit height
                $scale[] = $newHeight / $srcHeight;
            }

            if ($flags & self::FILL) {
                $scale = [max($scale)];
            }

            if ($flags & self::SHRINK_ONLY) {
                $scale[] = 1;
            }

            $scale = min($scale);
            $newWidth = (int) round($srcWidth * $scale);
            $newHeight = (int) round($srcHeight * $scale);
        }

        return [max($newWidth, 1), max($newHeight, 1)];
    }

    /**
     * @param int|string $left
     * @param int|string $top
     * @param int|string $width
     * @param int|string $height
     * @return $this
     */
    public function crop(int|string $left, int|string $top, int|string $width, int|string $height): static
    {
        [$r['x'], $r['y'], $r['width'], $r['height']]
            = static::calculateCutout($this->getWidth(), $this->getHeight(), $left, $top, $width, $height);
        if (gd_info()['GD Version'] === 'bundled (2.1.0 compatible)') {
            $this->image = imagecrop($this->image, $r);
            imagesavealpha($this->image, true);
        } else {
            $newImage = static::fromBlank($r['width'], $r['height'], self::RGB(0, 0, 0, 127))->getImageResource();
            imagecopy($newImage, $this->image, 0, 0, $r['x'], $r['y'], $r['width'], $r['height']);
            $this->image = $newImage;
        }
        return $this;
    }

    /**
     * @param int $srcWidth
     * @param int $srcHeight
     * @param int|string $left
     * @param int|string $top
     * @param int|string $newWidth
     * @param int|string $newHeight
     * @return array
     */
    public static function calculateCutout(int $srcWidth, int $srcHeight, int|string $left, int|string $top, int|string $newWidth, int|string $newHeight): array {
        if (self::isPercent($newWidth)) {
            $newWidth = (int) round($srcWidth / 100 * $newWidth);
        }
        if (self::isPercent($newHeight)) {
            $newHeight = (int) round($srcHeight / 100 * $newHeight);
        }
        if (self::isPercent($left)) {
            $left = (int) round(($srcWidth - $newWidth) / 100 * $left);
        }
        if (self::isPercent($top)) {
            $top = (int) round(($srcHeight - $newHeight) / 100 * $top);
        }
        if ($left < 0) {
            $newWidth += $left;
            $left = 0;
        }
        if ($top < 0) {
            $newHeight += $top;
            $top = 0;
        }
        $newWidth = min($newWidth, $srcWidth - $left);
        $newHeight = min($newHeight, $srcHeight - $top);
        return [$left, $top, $newWidth, $newHeight];
    }

    /**
     * @return $this
     */
    public function sharpen(): static
    {
        imageconvolution($this->image, [
            [-1, -1, -1],
            [-1, 24, -1],
            [-1, -1, -1],
        ], 16, 0);
        return $this;
    }

    /**
     * @param \Suna\Utils\Image $image
     * @param int|string $left
     * @param int|string $top
     * @param int $opacity
     * @return $this
     */
    public function place(self $image, int|string $left = 0, int|string $top = 0, int $opacity = 100): static
    {
        $opacity = max(0, min(100, $opacity));
        if ($opacity === 0) {
            return $this;
        }

        $width = $image->getWidth();
        $height = $image->getHeight();

        if (self::isPercent($left)) {
            $left = (int) round(($this->getWidth() - $width) / 100 * $left);
        }

        if (self::isPercent($top)) {
            $top = (int) round(($this->getHeight() - $height) / 100 * $top);
        }

        $output = $input = $image->image;
        if ($opacity < 100) {
            $tbl = [];
            for ($i = 0; $i < 128; $i++) {
                $tbl[$i] = round(127 - (127 - $i) * $opacity / 100);
            }

            $output = imagecreatetruecolor($width, $height);
            imagealphablending($output, false);
            if (!$image->isTrueColor()) {
                $input = $output;
                imagefilledrectangle($output, 0, 0, $width, $height, imagecolorallocatealpha($output, 0, 0, 0, 127));
                imagecopy($output, $image->image, 0, 0, 0, 0, $width, $height);
            }
            for ($x = 0; $x < $width; $x++) {
                for ($y = 0; $y < $height; $y++) {
                    $c = \imagecolorat($input, $x, $y);
                    $c = ($c & 0xFFFFFF) + ($tbl[$c >> 24] << 24);
                    \imagesetpixel($output, $x, $y, $c);
                }
            }
            imagealphablending($output, true);
        }

        imagecopy($this->image, $output, $left, $top, 0, 0, $width, $height);
        return $this;
    }

    /**
     * @param string $file
     * @param int|null $quality
     * @param int|null $type
     * @throws \Suna\Exceptions\ImageException
     */
    public function save(string $file, int $quality = null, int $type = null): void
    {
        if ($type === null) {
            $extensions = array_flip(self::FORMATS) + ['jpg' => self::JPEG];
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!isset($extensions[$ext])) {
                throw new InvalidArgumentException("Unsupported file extension '$ext'.");
            }
            $type = $extensions[$ext];
        }

        $this->output($type, $quality, $file);
    }

    /**
     * @param int $type
     * @param int|null $quality
     * @return string
     * @throws \Throwable
     */
    public function toString(int $type = self::JPEG, int $quality = null): string
    {
        return Helpers::capture(function () use ($type, $quality): void {
            $this->output($type, $quality);
        });
    }

    /**
     * @return string
     * @throws \Throwable
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @param int $type
     * @param int|null $quality
     * @throws \Suna\Exceptions\ImageException
     */
    public function send(int $type = self::JPEG, int $quality = null): void
    {
        header('Content-Type: ' . self::typeToMimeType($type));
        $this->output($type, $quality);
    }

    /**
     * @param int $type
     * @param int|null $quality
     * @param string|null $file
     * @throws \Suna\Exceptions\ImageException
     */
    private function output(int $type, ?int $quality, string $file = null): void
    {
        switch ($type) {
            case self::JPEG:
                $quality = $quality === null ? 85 : max(0, min(100, $quality));
                $success = @imagejpeg($this->image, $file, $quality); // @ is escalated to exception
                break;

            case self::PNG:
                $quality = $quality === null ? 9 : max(0, min(9, $quality));
                $success = @imagepng($this->image, $file, $quality); // @ is escalated to exception
                break;

            case self::GIF:
                $success = @imagegif($this->image, $file); // @ is escalated to exception
                break;

            case self::WEBP:
                $quality = $quality === null ? 80 : max(0, min(100, $quality));
                $success = @imagewebp($this->image, $file, $quality); // @ is escalated to exception
                break;

            case self::BMP:
                $success = @imagebmp($this->image, $file); // @ is escalated to exception
                break;

            default:
                throw new InvalidArgumentException("Unsupported image type '$type'.");
        }
        if (!$success) {
            throw new ImageException(Helpers::getLastError() ?: 'Unknown error');
        }
    }

    /**
     * @param string $name
     * @param array $args
     * @return mixed
     * @throws \ReflectionException
     */
    public function __call(string $name, array $args): mixed
    {
        $function = 'image' . $name;
        if (!function_exists($function)) {
            Obj::strictCall(static::class, $name);
        }

        foreach ($args as $key => $value) {
            if ($value instanceof self) {
                $args[$key] = $value->getImageResource();

            } elseif (is_array($value) && isset($value['red'])) { // rgb
                $args[$key] = imagecolorallocatealpha(
                    $this->image,
                    $value['red'],
                    $value['green'],
                    $value['blue'],
                    $value['alpha'],
                ) ?: imagecolorresolvealpha(
                    $this->image,
                    $value['red'],
                    $value['green'],
                    $value['blue'],
                    $value['alpha'],
                );
            }
        }
        $res = $function($this->image, ...$args);
        return $res instanceof \GdImage ? $this->setImageResource($res) : $res;
    }

    /**
     *
     */
    public function __clone()
    {
        ob_start(function () {});
        imagegd2($this->image);
        $this->setImageResource(imagecreatefromstring(ob_get_clean()));
    }

    /**
     * @param int|string $num
     * @return bool
     */
    private static function isPercent(int|string &$num): bool
    {
        if (is_string($num) && str_ends_with($num, '%')) {
            $num = (float) substr($num, 0, -1);
            return true;
        } elseif (is_int($num) || $num === (string) (int) $num) {
            $num = (int) $num;
            return false;
        }
        throw new InvalidArgumentException("Expected dimension in int|string, '$num' given.");
    }

    /**
     * Prevents serialization.
     */
    public function __sleep(): array
    {
        throw new NotSupportedException('You cannot serialize or unserialize ' . self::class . ' instances.');
    }
}