@php
    $data = json_decode($draft->lampiran);
    $index = 0;
    $length = count($data);
@endphp
@foreach ($data as $lampiran)
    <div style="page-break-after: {{ ($index != $length - 1) ? "always" : "never" }};">
        <div class="header-attachment">
            <table style="text-align: justify">
                <tbody>
                    <tr>
                        <td style="width: 70px; vertical-align: top;"><span class="attachment-list-number">LAMPIRAN </span></td>
                        @include('pdf.layouts.attachment.header')
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="clearfix"></div>
        <div class="content-attachment">
            <p style="line-height: 15px; text-align: justify;">
                {!! $lampiran !!}<br>
            </p>
        </div>
        @include('pdf.layouts.signature')
    </div>
    @php
        $index++;
    @endphp
@endforeach
