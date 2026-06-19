<?php

file_put_contents('.htpasswd', 'NOME_DO_USUARIO:' . password_hash('NOVA_SENHA', PASSWORD_BCRYPT) . PHP_EOL);
