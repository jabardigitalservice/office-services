@if ($draft->lampiran != null)
    <div style="page-break-after: {{ $draft->lampiran2 != null ? "always" : "never" }};">
        <div class="header-attachment">
            <table style="text-align: justify">
                <tbody>
                    <tr>
                        <td style="width: 70px; vertical-align: top;">LAMPIRAN I</td>
                        @include('pdf.layouts.attachment.header')
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="clearfix"></div>
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
        <div class="header-attachment">
            <table style="text-align: justify">
                <tbody>
                    <tr>
                        <td style="width: 70px; vertical-align: top;">LAMPIRAN II</td>
                        @include('pdf.layouts.attachment.header')
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="clearfix"></div>
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
        <div class="header-attachment">
            <table style="text-align: justify">
                <tbody>
                    <tr>
                        <td style="width: 70px; vertical-align: top;">LAMPIRAN III</td>
                        @include('pdf.layouts.attachment.header')
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="clearfix"></div>
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
        <div class="header-attachment">
            <table style="text-align: justify">
                <tbody>
                    <tr>
                        <td style="width: 70px; vertical-align: top;">LAMPIRAN IV</td>
                        @include('pdf.layouts.attachment.header')
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="clearfix"></div>
        <div class="content-attachment">
            <p style="line-height: 15px; text-align: justify;">
                {!! $draft->lampiran4 !!}<br>
        </p>
        </div>
        @include('pdf.layouts.signature')
    </div>
@endif
