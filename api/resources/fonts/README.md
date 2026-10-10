# resources/fonts

TrueType copies of the viewer's fonts, for drawing the WhatsApp preview picture
on the server (`App\Support\PreviewPicture`). GD reads TrueType only, not the
WOFF2 files the viewer loads. From github.com/google/fonts, under the SIL Open
Font License (the OFL-*.txt files beside them).

GD cannot shape Tamil (vowel signs come out in the wrong place), so Tiro Tamil
is used for the single letter உ only. See PLAN.md > Design pass.
