<?php

function listImages($directory) {
    if (!is_dir($directory)) {
        die("Erro: A pasta '$directory' não existe.\n");
    }
    if (!is_readable($directory)) {
        die("Erro: Sem permissão para ler a pasta '$directory'.\n");
    }

    $files = scandir($directory);
    if ($files === false) {
        die("Erro ao listar arquivos na pasta '$directory'.\n");
    }

    // Filtra apenas imagens
    $images = array_values(array_filter($files, function ($file) use ($directory) {
        return preg_match('/\.(jpg|jpeg|png|gif)$/i', $file);
    }));

    if (empty($images)) {
        die("Nenhuma imagem encontrada na pasta '$directory'.\n");
    }

    echo "Imagens disponíveis:\n";
    foreach ($images as $index => $image) {
        echo "[$index] $image\n";
    }

    return array_values($images);
}

function imageToAscii($imagePath, $outputFile, $width = 80, $height = 40) {
    $chars = "@%#*+=-:. "; // Melhor escala de tons
    $charLen = strlen($chars) - 1;

    $image = imagecreatefromstring(file_get_contents($imagePath));
    if (!$image) {
        die("Erro ao carregar a imagem.\n");
    }

    $origWidth = imagesx($image);
    $origHeight = imagesy($image);
    $resized = imagecreatetruecolor($width, $height);
    imagecopyresized($resized, $image, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);

    $asciiArt = "";

    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $rgb = imagecolorat($resized, $x, $y);
            $colors = imagecolorsforindex($resized, $rgb);
            $gray = ($colors['red'] + $colors['green'] + $colors['blue']) / 3;

            // Garante que o índice esteja dentro dos limites
            $charIndex = intval(round($gray / 255 * $charLen)); // Garante que seja um número inteiro
            $charIndex = max(0, min($charIndex, $charLen)); // Garante que esteja no intervalo correto

            $asciiArt .= isset($chars[$charIndex]) ? $chars[$charIndex] . " " : " "; // Previne erros de índice

        }
        $asciiArt .= "\n";
    }

    imagedestroy($image);
    imagedestroy($resized);

    file_put_contents($outputFile, $asciiArt);
    echo "Arte ASCII salva em: $outputFile\n";
}

$imagesDir = __DIR__ . '/images';
$compiledDir = __DIR__ . '/compiled';

// Criar diretório compilado se não existir
if (!is_dir($compiledDir)) {
    mkdir($compiledDir, 0777, true);
}

// Listar imagens disponíveis
$images = listImages($imagesDir);

// Escolher uma imagem pelo índice
echo "Digite o número da imagem que deseja converter: ";
$choice = trim(fgets(STDIN));
$choice = (int) $choice;

if (!isset($images[$choice])) {
    die("Escolha inválida.\n");
}

$selectedImage = $images[$choice];
$imagePath = "$imagesDir/$selectedImage";
$outputPath = "$compiledDir/" . pathinfo($selectedImage, PATHINFO_FILENAME) . ".txt";

// Converter a imagem para ASCII e salvar
imageToAscii($imagePath, $outputPath, 60, 30); // Ajuste para mais detalhes

// Perguntar se deseja exibir o conteúdo no terminal
echo "Deseja exibir o conteúdo do ASCII Art no terminal? (s/n): ";
$showAscii = trim(fgets(STDIN));

if (strtolower($showAscii) === 's') {
    echo "\nConteúdo do ASCII Art:\n";
    echo file_get_contents($outputPath);
}
