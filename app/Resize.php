<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Resize extends Model
{
    /**
     * Set the image variable by using image create
     *
     * @param string $filename - The image filename
     */
    public static function resizeing_image($dest, $thumb_dest, $max_size) {
        $img_arr = getimagesize($dest);

        if ($img_arr['mime'] != '') {
            if ($img_arr['mime'] == 'image/jpg') {
                $file_ext = 'jpg';
            }
            if ($img_arr['mime'] == 'image/jpeg') {
                $file_ext = 'jpeg';
            }
            if ($img_arr['mime'] == 'image/gif') {
                $file_ext = 'gif';
            }
            if ($img_arr['mime'] == 'image/png') {
                $file_ext = 'png';
            }
            if ($img_arr['mime'] == 'image/webp') {
                $file_ext = 'webp';
            }

            $image = self::open_image($dest, $file_ext);

            $image_width = $img_arr[0];
            $image_height = $img_arr[1];

            //Construct a proportional size of new image
            $image_scale = min($max_size / $image_width, $max_size / $image_height);
            $new_image_width = ceil($image_scale * $image_width);
            $new_image_height = ceil($image_scale * $image_height);

            $image_resized = imagecreatetruecolor($new_image_width, $new_image_height);
            $im1 = imagecopyresampled($image_resized, $image, 0, 0, 0, 0, $new_image_width, $new_image_height, $image_width, $image_height);

            ob_start();
            imagewebp($image_resized);
            $image_contents = ob_get_clean();
            $s3 = \Storage::disk('s3');
            $s3->put($thumb_dest, $image_contents, 'public'); //Store on S3


//            if ($file_ext != "") {
//                if (strtolower($file_ext) == 'jpg' || strtolower($file_ext) == 'jpeg') {
//                    imagejpeg($image_resized, $thumb_dest);
//                }
//                if (strtolower($file_ext) == 'gif') {
//                    imagegif($image_resized, $thumb_dest);
//                }
//                if (strtolower($file_ext) == 'png') {
//                    imagepng($image_resized, $thumb_dest);
//                }
//            }

        }
    }

    public function crop_image($dest, $thumb_dest, $w, $h) {
        $img_arr = getimagesize($dest);

        if ($img_arr['mime'] != '') {
            if ($img_arr['mime'] == 'image/jpg') {
                $file_ext = 'jpg';
            }
            if ($img_arr['mime'] == 'image/jpeg') {
                $file_ext = 'jpeg';
            }
            if ($img_arr['mime'] == 'image/gif') {
                $file_ext = 'gif';
            }
            if ($img_arr['mime'] == 'image/png') {
                $file_ext = 'png';
            }
            if ($img_arr['mime'] == 'image/webp') {
                $file_ext = 'webp';
            }

            $image = $this->open_image($dest, $file_ext);

            $image_width = $img_arr[0];
            $image_height = $img_arr[1];

            if ($image_width < $w && $image_height < $h) {
                $thumb_width = $image_width;
                $thumb_height = $image_height;

                $w_diff = ($w - $thumb_width);
                $h_diff = ($h - $thumb_height);

                $x = ($w_diff / 2);
                $y = ($h_diff / 2);
            } else {
                if ($image_width >= $w) {
                    $r = (double) ($w / $image_width);

                    $thumb_width = $w;
                    $thumb_height = $image_height * $r;

                    if ($thumb_height < $h) {
                        $r = (double) ($h / $image_height);

                        $thumb_width = $image_width * $r;
                        $thumb_height = $h;
                    }
                } else if ($image_height >= $h) {
                    $r = (double) ($h / $image_height);

                    $thumb_width = $image_width * $r;
                    $thumb_height = $h;

                    if ($thumb_width < $w) {
                        $r = (double) ($w / $image_width);

                        $thumb_width = $w;
                        $thumb_height = $image_height * $r;
                    }
                }

                if ($thumb_width < $w) {
                    $w_diff = ($w - $thumb_width);
                    $x = ($w_diff / 2);
                }
                if ($thumb_height < $h) {
                    $h_diff = ($h - $thumb_height);
                    $y = ($h_diff / 2);
                }
            }

            $image_resized = imagecreatetruecolor($w, $h);
            $back = imagecolorallocate($image_resized, 255, 255, 255);
            imagecolortransparent($image_resized, $back);
            imagefilledrectangle($image_resized, 0, 0, $w, $h, $back);
            $im1 = imagecopyresampled($image_resized, $image, $x, $y, 0, 0, $thumb_width, $thumb_height, $image_width, $image_height);

            if ($file_ext != "") {
                if (strtolower($file_ext) == 'jpg' || strtolower($file_ext) == 'jpeg') {
                    imagejpeg($image_resized, $thumb_dest);
                }
                if (strtolower($file_ext) == 'gif') {
                    imagegif($image_resized, $thumb_dest);
                }
                if (strtolower($file_ext) == 'png') {
                    imagepng($image_resized, $thumb_dest);
                }
            }
        }
    }

    public static function open_image($file, $file_type) {
        if ($file_type != "") {
            if ($file_type == 'jpg' || $file_type == 'jpeg') {
                $im = imagecreatefromjpeg($file);
                if ($im !== false) {
                    return $im;
                }
            }
            if ($file_type == 'gif') {
                $im = imagecreatefromgif($file);

                if ($im !== false) {
                    return $im;
                }
            }
            if ($file_type == 'png') {
                $im = imagecreatefrompng($file);
                if ($im !== false) {
                    return $im;
                }
            }
            if ($file_type == 'webp') {
                $im = imagecreatefromwebp($file);
                if ($im !== false) {
                    return $im;
                }
            }
            return false;
        }
    }

    public static function resizeing_image_mark($dest,$target) {
        $watermark_png_file = 'assets/watermark/180_150_watermark.png';
        $watermark_png_file_200 = 'assets/watermark/480_300_watermark.png';
        $watermark_png_file_400 = 'assets/watermark/480_300_watermark.png';
        $watermark_png_file_600 = 'assets/watermark/768_400_watermark.png';
        $watermark_png_file_800 = 'assets/watermark/992_500_watermark.png';
        $watermark_png_file_1000 = 'assets/watermark/1200_600_watermark.png';

        $img_arr = getimagesize($dest);

        if ($img_arr['mime'] != '') {
            if ($img_arr['mime'] == 'image/jpg') {
                $file_ext = 'jpg';
            }
            if ($img_arr['mime'] == 'image/jpeg') {
                $file_ext = 'jpeg';
            }
            if ($img_arr['mime'] == 'image/gif') {
                $file_ext = 'gif';
            }
            if ($img_arr['mime'] == 'image/png') {
                $file_ext = 'png';
            }
            if ($img_arr['mime'] == 'image/webp') {
                $file_ext = 'webp';
            }

            $image = self::open_image($dest, $file_ext);

            $image_width = $img_arr[0];
            $image_height = $img_arr[1];

            $new_image_width = $image_width;
            $new_image_height = $image_height;

            if($new_image_width > 1000)
            {
                //calculate center position of watermark image
                $watermark_left = (($new_image_width - 290) / 2); //watermark left
                $watermark_bottom = (($new_image_height - 80) / 2); //watermark bottom
                $w_width = 407;
                $w_height = 81;

                $watermark = imagecreatefrompng($watermark_png_file_1000); //watermark image

            }
            else if($new_image_width > 800)
            {
                //calculate center position of watermark image
                $watermark_left = (($new_image_width - 290) / 2); //watermark left
                $watermark_bottom = (($new_image_height - 80) / 2); //watermark bottom
                $w_width = 347;
                $w_height = 70;

                $watermark = imagecreatefrompng($watermark_png_file_800); //watermark image

            }
            else if($new_image_width > 600)
            {
                $w_width = 282;
                $w_height = 57;

                //calculate center position of watermark image
                $watermark_left = (($new_image_width - $w_width) / 2); //watermark left
                $watermark_bottom = (($new_image_height - $w_height) / 2); //watermark bottom

                $watermark = imagecreatefrompng($watermark_png_file_600); //watermark image

            }
            else if($new_image_width > 400)
            {
                $w_width = 209;
                $w_height = 42;

                //calculate center position of watermark image
                $watermark_left = (($new_image_width - $w_width) / 2); //watermark left
                $watermark_bottom = (($new_image_height - $w_height) / 2); //watermark bottom

                $watermark = imagecreatefrompng($watermark_png_file_400); //watermark image

            }
            else if($new_image_width > 200)
            {
                $w_width = 209;
                $w_height = 42;

                //calculate center position of watermark image
                $watermark_left = (($new_image_width - $w_width) / 2); //watermark left
                $watermark_bottom = (($new_image_height - $w_height) / 2); //watermark bottom

                $watermark = imagecreatefrompng($watermark_png_file_400); //watermark image

            }
            else
            {
                $w_width = 137;
                $w_height = 27;

                //calculate center position of watermark image
                $watermark_left = (($new_image_width - $w_width) / 2); //watermark left
                $watermark_bottom = (($new_image_height - $w_height) / 2); //watermark bottom

                $watermark = imagecreatefrompng($watermark_png_file); //watermark image

            }

            $image_resized = imagecreatetruecolor($new_image_width, $new_image_height);
            $im1 = imagecopyresampled($image_resized, $image, 0, 0, 0, 0, $new_image_width, $new_image_height, $image_width, $image_height);

            //use PHP imagecopy() to merge two images.
            //imagecopy($image_resized, $watermark, $watermark_left, $watermark_bottom, 0, 0, 290, 80); //merge image 180,39 is a watermark image real size

            imagecopy($image_resized,  $watermark, $watermark_left, $watermark_bottom, 0, 0, $w_width, $w_height); //merge image 180,39 is a watermark image real size

            ob_start();
            imagewebp($image_resized);
            $image_contents = ob_get_clean();
            $s3 = \Storage::disk('s3');
            $s3->put($target, $image_contents, 'public'); //Store on S3

//            if ($file_ext != "") {
//                if (strtolower($file_ext) == 'jpg' || strtolower($file_ext) == 'jpeg') {
//                    imagejpeg($image_resized, $dest);
//                }
//                if (strtolower($file_ext) == 'gif') {
//                    imagegif($image_resized, $dest);
//                }
//                if (strtolower($file_ext) == 'png') {
//                    imagepng($image_resized, $dest);
//                }
//                if (strtolower($file_ext) == 'webp') {
//                    imagewebp($image_resized, $dest);
//                }
//            }
        }

        //free up memory
        imagedestroy($image_resized);
    }


    public static function resizeing_image_mark_slider($dest,$target) {
        $watermark_png_file = 'assets/watermark/480_300_watermark.png';
        $img_arr = getimagesize($dest);

        if ($img_arr['mime'] != '') {
            if ($img_arr['mime'] == 'image/jpg') {
                $file_ext = 'jpg';
            }
            if ($img_arr['mime'] == 'image/jpeg') {
                $file_ext = 'jpeg';
            }
            if ($img_arr['mime'] == 'image/gif') {
                $file_ext = 'gif';
            }
            if ($img_arr['mime'] == 'image/png') {
                $file_ext = 'png';
            }
            if ($img_arr['mime'] == 'image/webp') {
                $file_ext = 'webp';
            }
            $image = self::open_image($dest, $file_ext);

            $image_width = $img_arr[0];
            $image_height = $img_arr[1];


            $new_image_width = $image_width;
            $new_image_height = $image_height;

            $w_width = 209;
            $w_height = 42;

            //calculate center position of watermark image
            $watermark_left = (($new_image_width - $w_width) / 2); //watermark left
            $watermark_bottom = (($new_image_height - $w_height) / 2); //watermark bottom

            $watermark = imagecreatefrompng($watermark_png_file); //watermark image

            $image_resized = imagecreatetruecolor($new_image_width, $new_image_height);
            $im1 = imagecopyresampled($image_resized, $image, 0, 0, 0, 0, $new_image_width, $new_image_height, $image_width, $image_height);
            ob_start();
            imagecopy($image_resized,  $watermark, $watermark_left, $watermark_bottom, 0, 0, $w_width, $w_height); //merge image 180,39 is a watermark image real size

            if ($file_ext != "") {


                imagewebp($image_resized);
                $image_contents = ob_get_clean();
                $s3 = \Storage::disk('s3');
                $s3->put($target, $image_contents, 'public'); //Store on S3
//                if (strtolower($file_ext) == 'jpg' || strtolower($file_ext) == 'jpeg') {
//                    imagejpeg($image_resized, $dest);
//                }
//                if (strtolower($file_ext) == 'gif') {
//                    imagegif($image_resized, $dest);
//                }
//                if (strtolower($file_ext) == 'png') {
//                    imagepng($image_resized, $dest);
//                }
//                if (strtolower($file_ext) == 'webp') {
//                    imagewebp($image_resized, $dest);
//                }
            }
        }

        //free up memory
        imagedestroy($image_resized);
    }
}
