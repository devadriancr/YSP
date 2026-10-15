{{-- Delivery instruction (YPU204B): one row per requirement, grouped by part --}}
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 96pt 36pt 30pt 36pt; }

        body { font-family: Helvetica, Arial, sans-serif; font-size: 8.5pt; color: #000; }

        header { position: fixed; top: -78pt; left: 0; right: 0; height: 70pt; }
        header .program { position: absolute; left: 0; top: 0; font-size: 7.5pt; }
        header .title { position: absolute; left: 0; right: 0; top: 0; text-align: center; font-size: 11pt; }
        header .page { position: absolute; right: 0; top: 0; font-size: 9pt; }
        header .page-number:after { content: counter(page); }
        header .supplier { position: absolute; left: 0; top: 26pt; font-size: 9pt; }
        header .issued { position: absolute; right: 12pt; top: 22pt; font-size: 9pt; }
        header .company { position: absolute; right: 0; top: 40pt; font-size: 9pt; }

        table { width: 100%; border-collapse: collapse; }
        th, td { border: 0.6pt solid #000; padding: 1pt 3pt; height: 10pt; }
        th { font-weight: normal; text-align: center; }
        td { text-align: center; }
        td.part { text-align: left; }
        td.number { text-align: right; padding-right: 8pt; }
        tr { page-break-inside: avoid; }
    </style>
</head>

<body>
    <header>
        <div class="program">YPU204B</div>
        <div class="title">DELIVERY INSTRUCTION (Weekly)</div>
        <div class="page">P.&nbsp;&nbsp;<span class="page-number"></span></div>
        <div class="supplier">Supplier&nbsp;&nbsp;{{ $vendor }}&nbsp;&nbsp;&nbsp;{{ $vendorName }}</div>
        <div class="issued">Issued date&nbsp;&nbsp;&nbsp;{{ $issued->format('d/m/Y') }}</div>
        <div class="company">{{ config('infor.company_name') }}</div>
    </header>

    <table>
        <colgroup>
            <col style="width: 20%">
            <col style="width: 8%">
            <col style="width: 14%">
            <col style="width: 13%">
            <col style="width: 10%">
            <col style="width: 18%">
            <col style="width: 12%">
        </colgroup>
        <thead>
            <tr>
                <th>Parts No</th>
                <th>. SNP</th>
                <th>Order No</th>
                <th>Delivery Date</th>
                <th>Shift</th>
                <th>Quantity</th>
                <th>Box Qty</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($parts as $rows)
                @foreach ($rows->values() as $i => $row)
                    <tr>
                        <td class="part">{{ $i === 0 ? $row['part'] : '' }}</td>
                        <td>{{ $i === 0 ? $row['snp'] : '' }}</td>
                        <td>{{ $row['order'] }}</td>
                        <td>{{ $row['date'] }}</td>
                        <td></td>
                        <td class="number">{{ number_format($row['quantity']) }}</td>
                        <td>{{ $row['boxes'] }}</td>
                    </tr>
                @endforeach
                @unless ($loop->last)
                    <tr>
                        <td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                    </tr>
                @endunless
            @endforeach
        </tbody>
    </table>
</body>

</html>
