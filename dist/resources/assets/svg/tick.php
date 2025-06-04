<?php
$strokeWidth = $strokeWidth ?? 1.5;
$color = $color ?? 'currentColor';
$width = $width ?? 24;
$height = $height ?? $width;
?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="<?php echo $width; ?>" height="<?php echo $height; ?>" color="<?php echo $color; ?>" fill="none">
    <path d="M5 14L8.5 17.5L19 6.5" stroke="<?php echo $color; ?>" stroke-width="<?php echo $strokeWidth; ?>" stroke-linecap="round" stroke-linejoin="round"></path>
</svg> 