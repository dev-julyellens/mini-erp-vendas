<?php

declare(strict_types=1);

/**
 * Tabela oculta visualmente com os mesmos dados do gráfico (leitores de tela).
 *
 * @var string $summaryId
 * @var string $caption
 * @var list<string> $headers
 * @var list<list<string>> $rows
 */

if (empty($rows) || empty($headers))
{
    return;
}

?>
<div id="<?= htmlspecialchars($summaryId, ENT_QUOTES, 'UTF-8') ?>" class="visually-hidden">
    <table>
        <caption><?= htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') ?></caption>
        <thead>
            <tr>
                <?php foreach ($headers as $header): ?>
                    <th scope="col"><?= htmlspecialchars($header, ENT_QUOTES, 'UTF-8') ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($row as $cell): ?>
                        <td><?= htmlspecialchars($cell, ENT_QUOTES, 'UTF-8') ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>