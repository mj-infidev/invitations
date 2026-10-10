{{--
  Text in each language shown, from InvitationPage::text(), label(), date() or time():
  <x-bi :text="$page->text($page->get('bride.name'))" class="name" />
  One element per language, each with its lang attribute; the page shows only
  the one matching <html lang>. Scenes animate whole lines, never letters.
--}}
@props(['text', 'as' => 'span'])
@foreach ($text as $lang => $value)
<{{ $as }} lang="{{ $lang }}" {{ $attributes->class(['bi']) }}>{{ $value }}</{{ $as }}>
@endforeach
