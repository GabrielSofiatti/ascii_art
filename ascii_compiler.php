<?php

function listImages($directory) {
    if (!is_dir($directory)) {
        exit("Erro: A pasta '$directory' não existe.\n");
    }
    if (!is_readable($directory)) {
        exit("Erro: Sem permissão para ler a pasta '$directory'.\n");
    }

    $files = scandir($directory);
    if ($files === false) {
        exit("Erro ao listar arquivos na pasta '$directory'.\n");
    }

    $images = array_values(array_filter($files, function ($file) use ($directory) {
        return preg_match('/\.(jpg|jpeg|png|gif)$/i', $file);
    }));

    if (empty($images)) {
        exit("Nenhuma imagem encontrada na pasta '$directory'.\n");
    }

    echo "Imagens disponíveis:\n";
    foreach ($images as $index => $image) {
        echo "[$index] $image\n";
    }

    return $images;
}

function imageToAscii($imagePath, $outputFile, $width = 120, $height = 60) {
    $chars = "@#%*+=-:. ";
    $charLen = strlen($chars) - 1;

    $image = imagecreatefromstring(file_get_contents($imagePath));
    if (!$image) {
        exit("Erro ao carregar a imagem.\n");
    }

    $origWidth = imagesx($image);
    $origHeight = imagesy($image);

    // Calcular nova altura proporcional ao width
    $aspectRatio = $origWidth / $origHeight;
    $newHeight = (int) ($height);// * $aspectRatio);
    $newWidth = (int) ($width);// * $aspectRatio);

    // var_dump($newHeight ,$newWidth, $aspectRatio);

    $resized = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

    $asciiArt = "";

    for ($y = 0; $y < $newHeight; $y++) {
        for ($x = 0; $x < $newWidth; $x++) {
            $rgb = imagecolorat($resized, $x, $y);
            $colors = imagecolorsforindex($resized, $rgb);
            $gray = ($colors['red'] * 0.3 + $colors['green'] * 0.59 + $colors['blue'] * 0.11);

            $charIndex = intval(round($gray / 255 * $charLen));
            $asciiArt .= $chars[$charIndex] . "";
        }
        $asciiArt .= "\n";
    }

    imagedestroy($image);
    imagedestroy($resized);

    file_put_contents($outputFile, $asciiArt);
    echo "Arte ASCII salva em: $outputFile\n";
}

function asciiToImage($asciiFile, $outputImage) {
    $ascii = file($asciiFile, FILE_IGNORE_NEW_LINES);
    $width = strlen($ascii[0]);
    $height = count($ascii);

    $fontSize = 1; // Tamanho da fonte embutida do GD (1 a 5)
    $charSpacing = 6; // Largura estimada para cada caractere
    $lineSpacing = 8; // Altura estimada para cada linha

    // Criar a imagem
    $newWidth = $width * $charSpacing;
    $newHeight = $height * $lineSpacing;
    $image = imagecreatetruecolor($newWidth, $newHeight);

    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    imagefilledrectangle($image, 0, 0, $newWidth, $newHeight, $white);

    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $char = $ascii[$y][$x] ?? ' ';
            if ($char !== ' ') {
                imagestring($image, $fontSize, $x * $charSpacing, $y * $lineSpacing, $char, $black);
            }
        }
    }

    imagepng($image, $outputImage);
    imagedestroy($image);
    echo "Imagem ASCII convertida salva em: $outputImage\n";
}

$imagesDir = __DIR__ . '/images';
$compiledDir = __DIR__ . '/compiled';
$asciiImagesDir = __DIR__ . '/imagesASCII';

if (!is_dir($compiledDir)) {
    mkdir($compiledDir, 0777, true);
}
if (!is_dir($asciiImagesDir)) {
    mkdir($asciiImagesDir, 0777, true);
}

$images = listImages($imagesDir);

$choice = null;
do {
    echo "Digite o número da imagem que deseja converter: ";
    $choice = trim(fgets(STDIN));
    if (!ctype_digit($choice) || !isset($images[(int) $choice])) {
        echo "Escolha inválida. Tente novamente.\n";
        $choice = null;
    }
} while ($choice === null);

$selectedImage = $images[(int) $choice];
$imagePath = "$imagesDir/$selectedImage";
$outputPath = "$compiledDir/" . pathinfo($selectedImage, PATHINFO_FILENAME) . ".txt";

imageToAscii($imagePath, $outputPath, 550, 270);

do {
    echo "Deseja exibir o conteúdo do ASCII Art no terminal? (s/n): ";
    $showAscii = trim(fgets(STDIN));
} while (!in_array(strtolower($showAscii), ['s', 'n']));

if (strtolower($showAscii) === 's') {
    echo "\nConteúdo do ASCII Art:\n";
    echo file_get_contents($outputPath);
}

do {
    echo "Deseja converter o ASCII TXT para uma imagem? (s/n): ";
    $convertToImage = trim(fgets(STDIN));
} while (!in_array(strtolower($convertToImage), ['s', 'n']));

if (strtolower($convertToImage) === 's') {
    $asciiImagePath = "$asciiImagesDir/" . pathinfo($selectedImage, PATHINFO_FILENAME) . ".png";
    asciiToImage($outputPath, $asciiImagePath);
}
