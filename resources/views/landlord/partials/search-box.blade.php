{{-- GET search box for a page banner. $action, $placeholder, $label; reads ?search=. --}}
<form method="GET" action="{{ $action }}" class="searchbar" role="search">
    <label for="pageSearch" class="sr-only">{{ $label }}</label>
    <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
    <input type="search" id="pageSearch" name="search" value="{{ request('search') }}" placeholder="{{ $placeholder }}" class="input" autocomplete="off">
</form>
