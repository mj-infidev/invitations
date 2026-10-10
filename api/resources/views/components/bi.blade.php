{{--
  Text in each language shown, from InvitationPage::text(), label(), date() or time():
  <x-bi :text="$page->text($page->get('bride.name'))" class="name" />
  Tamil comes first when both are shown; the second language gets .bi--second.
  One element per language, so scenes animate a whole line, never letters.
--}}
@props(['text', 'as' => 'span'])
@foreach ($text as $lang => $value)
<{{ $as }} lang="{{ $lang }}" {{ $attributes->class(['bi', 'bi--second' => ! $loop->first]) }}>{{ $value }}</{{ $as }}>
@endforeach
