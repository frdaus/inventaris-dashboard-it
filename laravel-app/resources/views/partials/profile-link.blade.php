<a class="profile-link" href="{{ route('profile.edit') }}" aria-label="Profil saya: {{ auth()->user()->username }}">
    <span class="profile-avatar" aria-hidden="true">
        @if (auth()->user()->profile_photo_path)
            <img src="{{ route('profile.photo') }}" alt="">
        @else
            <span>{{ strtoupper(substr(auth()->user()->username, 0, 1)) }}</span>
        @endif
    </span>
    <span class="profile-link-copy">
        <strong>{{ auth()->user()->username }}</strong>
        <small>Profil Saya</small>
    </span>
</a>
