<x-subnav label="Kebiasaan" :items="[
    ['label' => 'Kebiasaan', 'concept' => 'fl', 'url' => route('user.habits.index'), 'active' => request()->routeIs('user.habits.*')],
    ['label' => 'Target', 'icon' => 'target', 'url' => route('user.goals.index'), 'active' => request()->routeIs('user.goals.*')],
]" />
