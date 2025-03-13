<?php

//    Copyright 2023 - Ryan Honeyman

include_once 'mainbase.class.php';

class Main extends LWPLib\MainBase
{
    function replaceValues($string, $values)
    {
        if (!is_null($values) && is_array($values)) {
            $replace = array();
            foreach ($values as $key => $value) { $replace['{{'.$key.'}}'] = ((is_array($value)) ? implode('|',array_filter(array_unique($value))) : ((is_bool($value)) ? json_encode($value) : $value)); }

            $string = str_replace(array_keys($replace),array_values($replace),$string);
        }

        return $string;
    }
}
