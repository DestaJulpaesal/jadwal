@props(['user'])

<div
    x-data="{
        open: false,
        toggleMenu(event) {
            const button = event.currentTarget;
            this.open = !this.open;
            if (!this.open) return;

            this.$nextTick(() => {
                const rect = button.getBoundingClientRect();
                const menu = this.$refs.menu;
                const menuRect = menu.getBoundingClientRect();
                const gap = 8;
                const left = Math.max(gap, Math.min(rect.right + gap, window.innerWidth - menuRect.width - gap));
                const top = Math.max(gap, Math.min(rect.bottom - menuRect.height, window.innerHeight - menuRect.height - gap));

                menu.style.left = `${left}px`;
                menu.style.top = `${top}px`;
            });
        }
    }"
    x-on:click.outside="open = false"
    x-on:keydown.escape.window="open = false"
>
    <template x-teleport="body">
        <div x-cloak x-show="open" x-ref="menu" x-transition class="sidebar-profile-menu sidebar-profile-menu-popover" id="sidebar-profile-menu" x-on:click.stop>
            <a href="{{ route('settings.edit') }}" class="sidebar-profile-menu-link" @if (request()->routeIs('settings.*')) aria-current="page" @endif>
                <x-icon name="zona-waktu" class="h-5 w-5" />
                Pengaturan
            </a>
            <a href="{{ route('profile.edit') }}" class="sidebar-profile-menu-link" @if (request()->routeIs('profile.*')) aria-current="page" @endif>
                <x-icon name="profil" class="h-5 w-5" />
                Profil
            </a>
        </div>
    </template>
    <button type="button" x-ref="button" class="sidebar-profile-card w-full text-left" aria-label="Buka menu akun" aria-controls="sidebar-profile-menu" x-on:click="toggleMenu($event)" x-bind:aria-expanded="open.toString()">
        <x-profile-avatar :user="$user" class="h-11 w-11 shrink-0" />
        <span class="sidebar-profile-copy">
            <span class="sidebar-profile-name">{{ $user->name }}</span>
            <span class="sidebar-profile-email">{{ $user->email }}</span>
        </span>
        <x-icon name="menu-aksi" class="h-5 w-5 shrink-0" />
    </button>
</div>
