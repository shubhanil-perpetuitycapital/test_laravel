@props(['active', 'on' => 'Active', 'off' => 'Hidden'])
<span @class(['badge rounded-pill', 'text-bg-success' => $active, 'text-bg-light border' => ! $active])>{{ $active ? $on : $off }}</span>
