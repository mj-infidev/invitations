{{--
  Tamil Hindu wedding: seven full-screen scenes. Each is pinned while the guest
  scrolls and its animation follows the scroll (resources/js/viewer/templates/
  tamil-hindu-wedding). The markup here is each scene's finished state, so with
  reduced motion, or before the script loads, everything is simply shown.
  Each scene is one full-screen picture (public/scenes/tamil-hindu-wedding).
--}}
@extends('invitations.layout')

@section('scenes')
    @include('invitations.templates.tamil-hindu-wedding.opening')
    @include('invitations.templates.tamil-hindu-wedding.couple')
    @include('invitations.templates.tamil-hindu-wedding.families')
    @include('invitations.templates.tamil-hindu-wedding.muhurtham')
    @include('invitations.templates.tamil-hindu-wedding.venue')
    @include('invitations.templates.tamil-hindu-wedding.photos')
    @include('invitations.templates.tamil-hindu-wedding.welcome')
@endsection
