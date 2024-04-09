<?php

if (!isset($classes)) {
    $classes = '';
}

if (!isset($start)) {
    $start = 0;
}

if (!isset($end)) {
    $end = 1440;
}

$start = (int)$start;
$end   = (int)$end;

if (old($name) || old($name) === 0 || old($name) === '0') {
    $selected_value = intval(old($name));
} elseif ($value === null) {
    $selected_value = 'null';
} elseif ($value || $value === 0) {
    $selected_value = intval($value);
}

?>

<select name="{{ $name }}" class="time-picker demo-total-trigger">
    <option value="null">None</option>
    @for ($j = $start; $j <= $end; $j += 30)
        <option value="{{ $j }}" {{ $j === $selected_value ? 'selected' : '' }} >{{ date('h:i a', mktime(0, $j, 0)) }}</option>
    @endfor
</select>