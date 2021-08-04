# Simple-MediaLibrary

#### Cài Đặt

    Copy file migration vào thư mục migrations và chạy composer update
    Hoặc tự tạo file migration và copy content nếu không muốn chạy composer update
    Copy library.php vào thư mục config, đổi lại tên file tùy ý, và sửa lại đồng thời tên config trong file MediaModelTrait nếu thay đổi
    Copy Traits vào thư mục bất kỳ trong app/ và sửa lại namespace cho đúng

### Tùy chỉnh
	File MediaModelTrait là file sample về một fixed collection, ở đây là avatar
	File Product.php là sample về Model chứa slide collection, cũng như cách xóa ảnh đơn lẻ trong slide collection đó bằng uniq_path
