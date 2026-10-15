{{-- Identification cards (YPU202B): 3 per letter page, one per box --}}
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }

        body { margin: 0; font-family: Helvetica, Arial, sans-serif; color: #000; }

        .page { position: relative; height: 780pt; page-break-after: always; }
        .page:last-child { page-break-after: auto; }

        .card { position: absolute; left: 0; width: 612pt; height: 256pt; }
        .card + .card { border-top: 0.8pt dashed #555; }

        .abs { position: absolute; white-space: nowrap; }
        .caption { font-size: 7pt; }
        .value { font-size: 19pt; }
        .big { font-size: 26pt; }

        .parts-box { position: absolute; left: 50pt; top: 46pt; width: 490pt; height: 54pt; border: 0.8pt solid #000; }
        .rl-box { position: absolute; right: 0; top: 0; width: 48pt; height: 54pt; border-left: 0.8pt solid #000; }
        .part-number { position: absolute; left: 0; top: 13pt; width: 442pt; text-align: center; font-size: 30pt; }

        .id-card { left: 50pt; top: 207pt; width: 160pt; height: 13pt; border: 0.8pt solid #000; font-size: 10pt; padding-left: 3pt; }
        .digits { font-size: 6pt; letter-spacing: 7pt; }
    </style>
</head>

<body>
    @foreach ($labels->chunk(3) as $page)
        <div class="page">
            @foreach ($page->values() as $i => $label)
                <div class="card" style="top: {{ 6 + $i * 258 }}pt;">
                    <div class="abs caption" style="left: 105pt; top: 12pt;">Delivery Date</div>
                    <div class="abs caption" style="left: 215pt; top: 12pt;">Delivery Time</div>
                    <div class="abs caption" style="left: 328pt; top: 12pt;">Warehouse</div>
                    <div class="abs caption" style="left: 430pt; top: 12pt;">Next Process</div>
                    <div class="abs value" style="left: 100pt; top: 20pt;">{{ $label['delivery_date'] }}</div>
                    <div class="abs value" style="left: 330pt; top: 20pt;">{{ $label['warehouse'] }}</div>
                    <div class="abs" style="left: 410pt; top: 26pt; font-size: 10pt;">{{ $label['next_process'] }}</div>

                    <div class="parts-box">
                        <div class="abs caption" style="left: 4pt; top: 3pt;">Parts No.</div>
                        <div class="part-number">{{ $label['part'] }}</div>
                        <div class="rl-box">
                            <div class="abs caption" style="left: 3pt; top: 3pt;">R/L</div>
                            <div class="abs" style="left: 0; top: 20pt; width: 48pt; text-align: center; font-size: 14pt;">{{ $label['rl'] }}</div>
                        </div>
                    </div>

                    <div class="abs caption" style="left: 100pt; top: 104pt;">Pack-C</div>
                    <div class="abs caption" style="left: 258pt; top: 104pt;">SNP</div>
                    <div class="abs caption" style="left: 410pt; top: 104pt;">SEQ</div>
                    <div class="abs caption" style="left: 505pt; top: 104pt;">Truck No.</div>
                    <div class="abs big" style="left: 85pt; top: 110pt;">{{ $label['pack'] }}</div>
                    <div class="abs big" style="left: 245pt; top: 110pt;">{{ $label['snp'] }}</div>
                    <div class="abs big" style="left: 370pt; top: 110pt;">{{ $label['seq'] }}</div>
                    <div class="abs big" style="left: 505pt; top: 110pt;">{{ $label['truck'] }}</div>

                    <div class="abs caption" style="left: 50pt; top: 154pt;">Parts Name</div>
                    <div class="abs caption" style="left: 330pt; top: 154pt;">Order No.</div>
                    <div class="abs caption" style="left: 405pt; top: 154pt;">Order Quantity</div>
                    <div class="abs" style="left: 50pt; top: 164pt; font-size: 11pt;">{{ $label['description'] }}</div>
                    <div class="abs" style="left: 315pt; top: 164pt; font-size: 10pt;">{{ $label['order'] }}</div>
                    <div class="abs" style="left: 405pt; top: 164pt; width: 60pt; text-align: center; font-size: 10pt;">{{ number_format($label['order_quantity']) }}</div>
                    <img class="abs" src="{{ $label['qr'] }}" style="left: 498pt; top: 146pt; width: 48pt; height: 48pt;">

                    <div class="abs" style="left: 50pt; top: 184pt; font-size: 8pt;">Supplier&nbsp;&nbsp;&nbsp;{{ $label['vendor'] }}</div>
                    <div class="abs" style="left: 152pt; top: 182pt; font-size: 11pt;">{{ $label['vendor_name'] }}</div>

                    <div class="abs id-card">IDENTIFICATION CARD</div>
                    <div class="abs" style="left: 70pt; top: 226pt; font-size: 9pt;">{{ config('infor.company_name') }}</div>
                    <img class="abs" src="{{ $label['barcode'] }}" style="left: 368pt; top: 200pt; width: 178pt; height: 26pt;">
                    <div class="abs digits" style="left: 372pt; top: 228pt;">{{ $label['order'] }}</div>
                </div>
            @endforeach
        </div>
    @endforeach
</body>

</html>
