<?php
session_start();
include "controller/pdo.php";
include "controller/danh_muc.php";
include "controller/san_pham.php";
include "controller/users.php";
include "controller/gio_hang.php";
// include "header.php";
if (!isset($_SESSION['mycart'])) {
    $_SESSION['mycart'] = [];
}
if (!empty($_SESSION['mycart'])) {
    $pro = one_in_sp();
}

if (isset($_GET['act'])) {
    $act = $_GET['act'];
    switch ($act) {
        case 'dangky':
            if (isset($_POST['dangky']) && ($_POST['dangky'])) {
                $email = $_POST['email'];
                $username = $_POST['username'];
                $password = $_POST['password'];
                $one_email = user_email($email);
                $one_user = user_username($username);
                if ($email == $one_email['email']) {
                    $err = "Email đã tồn tại";
                    // header("location:sigup_gia_huy.php?err=" . $err);
                    header("location:signup_gia_huy.php?err=" . $err);
                    echo "Email đã tồn tại";
                } elseif ((filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
                    $err = "Email không đúng định dạng";
                    header("location:signup_gia_huy.php?err=" . $err);
                    echo "Email không đúng định dạng";
                } else if ($username == $one_user['username']) {
                    $err = "username đã tồn tại";
                    header("location:signup_gia_huy.php");
                    echo "Tên đăng nhập đã tồn tại";
                } else {
                    insert_user($email, $username, $password);
                    $thongbao = "Đăng kí tài khoản thành công";
                    header("location:signin_gia_huy.php?err=" . $thongbao);
                    die;
                }
            }
            break;
        case 'dangnhap':
            if (isset($_POST['dangnhap']) && ($_POST['dangnhap'])) {
                $username = $_POST['username'];
                $password = $_POST['password'];
                $check_user = check_user($username, $password);
                // print_r(is_array($check_user)) ;die;
                if ($check_user['role'] == "user") {
                    if (is_array($check_user)) {
                        $_SESSION['username'] = $check_user;
                        header("location:index.php");
                        die;
                    }
                } elseif ($check_user['role'] == "admin") {
                    if (is_array($check_user)) {
                        $_SESSION['username'] = $check_user;
                        header("location:admin/index.php");
                        die;
                    }
                }
                else {
                    $err = "Tài khoản hoặc mật khẩu không đúng !";
                    header("location:signin_gia_huy.php");
                    die;
                }
            }
            break;
        case 'thoat':
            session_unset();
            header("location:index.php");
            die;

        case 'quenmk':
            if (isset($_POST['guiemail']) && ($_POST['guiemail'])) {
                $email = $_POST['email'];
                $checkemail = checkemail($email);
                if (is_array($checkemail)) {
                    $thongbao = "Mật khẩu của bạn là: " . $checkemail['password'];
                    header("location: quenmk.php?thongbao=" . $thongbao);
                    die;
                } else {
                    $thongbao = "EMAIL không tồn tại!";
                    header("location: quenmk.php?thongbao=" . $thongbao);
                    die;
                }
            }
            include "./quenmk.php";
            break;
           
        case 'ctsp':
            if(isset($_GET['id'])&&($_GET['id']>0)){
                $id=$_GET['id'];    
                $listctsp=one_sp($id);
            }
            // header("location:index.php");
            include "./view/product_details.php";
            break;
            // Bị lỗi header 
        case 'buynow':
            if (isset($_POST['buynow']) && ($_POST['buynow'])) {
                update_cart(true);
            }
            header("location:index.php?act=thanhtoan");
            include "view/payment.php";

            // header("location:index.php?act=thanhtoan");
            break;

        case 'deletecart':
            if (isset($_GET['id'])) {
                $id = $_GET['id'];
                deletecart($id);
            } else {
                $_SESSION['mycart'] = [];
            }
            header("location:index.php");
            break;

        case 'submit':
            if (isset($_POST['update_click']) && ($_POST['update_click'])) {
                update_cart();
                header("location:index.php");
                die;
            } elseif (isset($_POST['order_click']) && ($_POST['order_click'])) {
                include "header.php";
                update_cart();
                // $quantity = $_POST['quantity'];
                // $price = $_POST['price'];


                // $one_sp=one_sp_order();
                // $id = $one_sp['id'] + 1;
                // var_dump($id); exit;
                // insert_shopping_cart_item($id,$quantity,$price,$product_id,$shopping_cart_id);
                include "view/payment.php";
                include "footer.php";
                die;
            }
            break;

        case 'thanhtoan':
            if (isset($_POST['thanhtoan']) && ($_POST['thanhtoan'])) {
                if (empty($_POST['name'])) {
                    $err = "Vui lòng nhập tên người nhận";
                } elseif (empty($_POST['phone'])) {
                    $err = "Vui lòng nhập số điện thoại";
                } elseif (empty($_POST['address'])) {
                    $err = "Vui lòng nhập địa chỉ";
                } elseif (empty($_POST['select'])) {
                    $err = "Vui lòng chọn phương thức thanh toán";
                } else {
                    // var_dump($_POST['phone']);
                    // die;
                    $pro = one_in_sp();
                    // var_dump($pro);exit;
                    $total = 0;
                    $orderProducts = array();
                    foreach ($pro as $cart) {
                        $orderProducts[] = $cart;
                        $total += $cart['price'] * $_SESSION['mycart'][$cart['id']];
                    }
                    $name = $_POST['name'];
                    $phone = $_POST['phone'];
                    $address = $_POST['address'];
                    $desc_order = $_POST['desc_order'];
                    $date_order = time();
                    insert_shop_order($name, $phone, $address, $desc_order, $total, $date_order);

                    $one_order = one_sp_order();
                    $one_order_id = $one_order['id'];
                    $array = "";
                    foreach ($orderProducts as $key => $order) {
                        $array .= "('" . $_SESSION['mycart'][$order['id']] . "','" . $order['id'] . "','" . $one_order_id . "', '" . $order['price'] . "')";
                        if ($key != count($orderProducts) - 1) {
                            $array .= ",";
                        }
                    }
                    // var_dump($array); exit;
                    insert_shopping_cart_item($array);
                }
            }
            // header("location:index.php");
            include "header.php";
            include "view/payment.php";
            include "footer.php";
            die;
            break;
        case '':
            break;
        case '':
            break;
        case '':
            break;
        case '':
            break;
    }
}
$list_dm = alldm();
$allsp = all_sp();
include "header.php";
if (isset($_GET['act'])) {
    $act = $_GET['act'];
    switch ($act) {
        case 'loaisp':
            if (isset($_GET['id']) && ($_GET['id'] > 0)) {
                $id = $_GET['id'];
                $loaisp = loaisp($id);
            }
            include "view/loaisp.php";
            break;
        case '':

            break;
        case '':

            break;
        case '':

            break;



        default:

            break;
    }
} else {

    include "main.php";
}


include "footer.php";
