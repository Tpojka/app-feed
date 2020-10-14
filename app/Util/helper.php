<?php
/**
 * Project trivago-feed.local
 * File: helper.php
 * Created by: tpojka
 * On: 14/10/2020
 */

if (! function_exists('formatErrorLine')) {
    function formatErrorLine(\Throwable $t)
    {
        return $t->getMessage() . ' ::: ' . $t->getFile() . ':' . $t->getLine();
    }
}
