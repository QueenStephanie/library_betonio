<?php
$c = file_get_contents(__DIR__ . '/app/librarian/books.php');
// Fix the broken HTML entity replacement in the SweetAlert JS
$old = "result.message.replace(/</g, '<').replace(/>/g, '>')";
$new = "result.message.replace(/</g, '<').replace(/>/g, '>')";
$c = str_replace($old, $new, $c);
file_put_contents(__DIR__ . '/app/librarian/books.php', $c);
echo "Fixed.\n";
