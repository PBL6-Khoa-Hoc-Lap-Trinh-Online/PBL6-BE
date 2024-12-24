<!DOCTYPE html>
<html>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<head>
    <title>Thông báo từ hệ thống</title>
</head>

<body>
    <div style="
    background-color: rgb(240, 255, 240);
    justify-content: center;
    border: 2px solid silver;
    margin-bottom: 3px;
    padding: 10px;
    text-align: center;
    ">
        <p style="font-style: italic;margin: 0;margin-bottom: 10px;">Xin chào bạn ! Bạn có một thông báo đến từ hệ thống Pharmacity !</p>
        <p style="margin: 0;margin-bottom: 5px;">{{ now() }}</p>
    </div>

    <div style="background-color: #007eff1c;padding: 20px;border: 2px dashed #007bff;">
        {!! $content !!}
        <p>Đặt hàng thành công! Đơn hàng của bạn là:</p>
    <table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;"> 
        <tr>
            <th colspan="2">Thông tin đơn hàng</th>
        </tr>
        <tr>
            <td>Mã đơn hàng</td>
            <td>' . $order->order_id . '</td>
        </tr>
        <tr>
            <td>Tổng tiền</td>
            <td>' . number_format($order->order_total_amount, 0, ',', '.') . ' VND</td>
        </tr>
        <tr>
            <td>Ngày tạo</td>
            <td>' . $order->order_created_at . '</td>
        </tr>
        <tr>
            <th colspan="2">Chi tiết đơn hàng</th>';

        // Duyệt qua mảng groupedDetails để hiển thị thông tin các sản phẩm
        foreach ($groupedDetails as $detail) {
            $content .= '
        <tr>
            <td>Mã sản phẩm</td>
            <td>' . $detail['product_id'] . '</td>
        </tr>
        <tr>
            <td>Số lượng</td>
            <td>' . $detail['order_quantity'] . '</td>
        </tr>
        <tr>
            <td>Giá</td>
            <td>' . number_format($detail['order_price'], 0, ',', '.') . ' VND</td>
        </tr>
        <tr>
            <td>Tổng giá</td>
            <td>' . number_format($detail['order_total_price'], 0, ',', '.') . ' VND</td>
        </tr>';
        }

        $content .= '</table>';
    </div>
    
    <div style="
    background-color: rgb(240, 255, 240);
    justify-content: center;
    border: 2px solid silver;
    margin-top: 3px;
    padding: 20px;
    text-align: center;
    ">
   
</div>
</body>

</html>
