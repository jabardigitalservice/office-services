@if ($draft->lampiran != null)
    <div style="page-break-after: {{ $draft->lampiran2 != null ? "always" : "never" }};">
        @include('pdf.layouts.attachment.header')
        <div class="content-attachment">
            <p style="line-height: 15px; text-align: justify;">
                {!! $draft->lampiran !!}<br>
            </p>
        </div>
        @include('pdf.layouts.signature')
    </div>
@endif
@if ($draft->lampiran2 != null)
    <div style="page-break-after: {{ $draft->lampiran3 != null ? "always" : "never" }};">
        @include('pdf.layouts.attachment.header')
        <div class="content-attachment">
            <p style="line-height: 15px; text-align: justify;">
                {!! $draft->lampiran2 !!}<br>
            </p>
        </div>
        @include('pdf.layouts.signature')
    </div>
@endif
@if ($draft->lampiran3 != null)
    <div style="page-break-after: {{ $draft->lampiran4 != null ? "always" : "never" }};">
        @include('pdf.layouts.attachment.header')
        <div class="content-attachment">
            <p style="line-height: 15px; text-align: justify;">
                {!! $draft->lampiran3 !!}<br>
            </p>
        </div>
        @include('pdf.layouts.signature')
    </div>
@endif
@if ($draft->lampiran4 != null)
    <div style="page-break-after: never">
        @include('pdf.layouts.attachment.header')
        <div class="content-attachment">
            <p style="line-height: 15px; text-align: justify;">
                {!! $draft->lampiran4 !!}<br>
        </p>
        </div>
        @include('pdf.layouts.signature')
    </div>
@endif
