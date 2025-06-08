<?php
$strokeWidth = $strokeWidth ?? 1.5;
$color = $color ?? 'currentColor';
$width = $width ?? 24;
$height = $height ?? $width;
?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="<?php echo $width; ?>" height="<?php echo $height; ?>" color="<?php echo $color; ?>" fill="none">
    <path d="M19.0005 4.99988L5.00049 18.9999M5.00049 4.99988L19.0005 18.9999" stroke="<?php echo $color; ?>" stroke-width="<?php echo $strokeWidth; ?>" stroke-linecap="round" stroke-linejoin="round"></path>
</svg>