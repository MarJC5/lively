<?php
$strokeWidth = $strokeWidth ?? 1.5;
$color = $color ?? 'currentColor';
$width = $width ?? 24;
$height = $height ?? $width;
?>

<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="<?php echo $width; ?>" height="<?php echo $height; ?>" color="<?php echo $color; ?>" fill="none">
    <path d="M17 17L21 21" stroke="<?php echo $color; ?>" stroke-width="<?php echo $strokeWidth; ?>" stroke-linecap="round" stroke-linejoin="round"></path>
    <path d="M19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19C15.4183 19 19 15.4183 19 11Z" stroke="<?php echo $color; ?>" stroke-width="<?php echo $strokeWidth; ?>" stroke-linecap="round" stroke-linejoin="round"></path>
</svg>