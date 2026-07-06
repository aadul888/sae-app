<?php
// Fix git safe.directory — run once before deploy
exec('git config --global --add safe.directory /www/wwwroot/sae.smakpal.sch.id 2>&1', $o, $rv);
echo "exit=$rv: " . implode("\n", $o);
