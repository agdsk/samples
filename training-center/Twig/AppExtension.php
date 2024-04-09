<?php

namespace AppBundle\Twig;

class AppExtension extends \Twig_Extension
{
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('managementCellRenderer', [$this, 'managementCellRenderer']),
        ];
    }

    public function managementCellRenderer($columnName, $columnDefinition, $Record)
    {
        if (array_key_exists('field', $columnDefinition)) {
            return htmlentities($Record[$columnDefinition['field']]);
        }

        if (array_key_exists('callback', $columnDefinition)) {
            return htmlentities($columnDefinition['callback']($Record));
        }

        if (array_key_exists('course_roster', $columnDefinition)) {
            return '<a href="/admin/session/' . $Record->id . '">View Roster</a>';
        } i
    }

    public function getName()
    {
        return 'app_extension';
    }
}
