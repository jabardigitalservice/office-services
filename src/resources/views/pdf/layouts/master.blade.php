<html>
    <head>
        <style>
            /** Define the margins of your page **/
            body {
                font-family: "Arial, Helvetica, sans-serif";
            }
            @page {
                margin: 85px 85.5px 80px 123.1px;
            }

            section#header-section {
                position: fixed;
                top: -60px;
                left: 0px;
                right: 0px;
                height: 0px;

                /** Extra personal styles **/
            }

            footer {
                position: fixed;
                bottom: -70px;
                left: 0px;
                right: 0px;
                height: 50px;

                /** Extra personal styles **/
                text-align: center;
                font-size: 9px;
                margin-left: 5px;
                text-align: center;
            }
            .qr-wrapper {
                position: absolute;
                left: 0;
                top: -10px;
                width: 65px;
            }
            .clearfix {
                clear: both;
            }
            .left-header {
                float: left;
                width: 353.5px;
            }

            .right-header {
                float: left;
                width: 229.5px;
            }

            .right-header__sub-left {
                float: left;
            }

            .right-header__sub-right {
                padding-left: 25px;
            }
            .table-collapse {
                border-collapse: collapse;
            }
            .table-collapse td {
                padding: 0;
                margin: 0;
            }
            .mini-padding-table td {
                padding: 1.8px;
                margin: 1.8px;
            }

            .mini-list-table td {
                padding: 0px 0px 1.8px 0px !important;
            }
            #header-content-section {
                margin-bottom: 55px;
            }
            #body-content-section {
                text-align: justify;
                line-height: 17px;
            }

            #body-content-section table {
                height: auto !important;
            }
            #body-content-section table td {
                line-height: 14px;
                vertical-align: top;
            }

            .header-attachment {
                margin-top: 11px;
                float: right;
                width: 385px;
                font-size: 11px;
                line-height: 15px;
                position: relative;
                right: -10px;
            }
            .content-attachment table {
                border-collapse: collapse;
                width: auto;
            }
            .content-attachment td {
                padding: 0;
                margin: 0;
                line-height: 16px
            }

            .attachment-list-number {
                counter-increment: item-counter;
            }

            .attachment-list-number:after {
                content: counter(item-counter, upper-roman) ""; /* by specifying the upper-roman as style the output would be in roman numbers */
            }
        </style>
    </head>
    <body>
        <!-- Define header and footer blocks before your content -->
        @if ($generateQrCode)
            <footer>
                <div class="qr-wrapper">
                    <img src="{{ storage_path('app/' . $generateQrCode) }}" alt="QR Code Signed" width="50px">
                    <p style="text-align: center; font-weight: bold; font-size: 9px; position: relative; top: -15px">{{ $verifyCode }}</p>
                </div>
                <div>
                    Dokumen ini telah ditandatangani secara elektronik menggunakan sertifikat elektronik yang diterbitkan oleh
                    <br>
                    Balai Sertifikasi Elektronik (BSrE) Badan Siber dan Sandi Negara
                </div>
            </footer>
        @endif

        <!-- Wrap the content of your PDF inside a main tag -->
        <main>
            @yield('content')
        </main>
    </body>
</html>
