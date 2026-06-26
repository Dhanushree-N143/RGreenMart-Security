<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

require_once $_SERVER["DOCUMENT_ROOT"] . "/dbconf.php";

$uploadDir = $_SERVER["DOCUMENT_ROOT"] . "/admin/categoryImages/";
if (!is_dir($uploadDir) && !mkdir($uploadDir,0755,true)) {
    die("Unable to create upload directory.");
}

$error = "";

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_category'])) {

    $name = trim(strip_tags($_POST['name'] ?? ''));
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

    if (!preg_match('/^[A-Za-z0-9 &().-]{2,50}$/',$name)) {
        $error = "Invalid category name.";
    } else {

        $check = $conn->prepare("SELECT id FROM categories WHERE name=? LIMIT 1");
        $check->execute([$name]);

        if ($check->fetch()) {
            $error = "Category already exists.";
        } else {

            if ($parent_id !== null) {
                $p = $conn->prepare("SELECT id FROM categories WHERE id=?");
                $p->execute([$parent_id]);
                if (!$p->fetch()) {
                    $error = "Invalid parent category.";
                }
            }

            $imageName = null;

            if ($error==="" && !empty($_FILES['image']['name'])) {

                if ($_FILES['image']['error']!==UPLOAD_ERR_OK) {
                    $error = "Image upload failed.";
                } elseif ($_FILES['image']['size']>2*1024*1024) {
                    $error = "Image must be under 2 MB.";
                } else {

                    $allowedExt=['jpg','jpeg','png','webp'];
                    $ext=strtolower(pathinfo($_FILES['image']['name'],PATHINFO_EXTENSION));

                    if(!in_array($ext,$allowedExt,true)){
                        $error="Invalid image format.";
                    } else {

                        $finfo=finfo_open(FILEINFO_MIME_TYPE);
                        $mime=finfo_file($finfo,$_FILES['image']['tmp_name']);
                        finfo_close($finfo);

                        if(!in_array($mime,['image/jpeg','image/png','image/webp'],true)){
                            $error="Invalid image type.";
                        } else {
                            $imageName=uniqid('cat_',true).".".$ext;

                            if(!move_uploaded_file($_FILES['image']['tmp_name'],$uploadDir.$imageName)){
                                $error="Unable to upload image.";
                            }
                        }
                    }
                }
            }

            if($error===""){
                try{
                    $stmt=$conn->prepare("INSERT INTO categories(name,parent_id,image) VALUES(?,?,?)");
                    $stmt->execute([$name,$parent_id,$imageName]);
                    header("Location: manage_categories.php");
                    exit;
                }catch(PDOException $e){
                    error_log($e->getMessage());
                    $error="Something went wrong. Please try again.";
                }
            }
        }
    }
}

$categories=$conn->query("
SELECT c.id,c.name,p.name AS parent_name,c.image
FROM categories c
LEFT JOIN categories p ON c.parent_id=p.id
ORDER BY c.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Categories</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="flex">
<?php require './common/admin_sidebar.php'; ?>
<main class="flex-1 p-6">
<div class="max-w-6xl mx-auto bg-white p-6 rounded-xl shadow">
<div class="flex justify-between items-center mb-6">
<h1 class="text-2xl font-bold text-indigo-600">Manage Categories</h1>
<button onclick="openModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg">+ Add Category</button>
</div>

<?php if($error): ?>
<div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?= htmlspecialchars($error,ENT_QUOTES,'UTF-8') ?></div>
<?php endif; ?>

<table class="w-full border text-sm">
<thead><tr><th class="p-3 border">ID</th><th class="p-3 border">Image</th><th class="p-3 border">Name</th><th class="p-3 border">Parent</th></tr></thead>
<tbody>
<?php foreach($categories as $cat): ?>
<tr>
<td class="p-3 border"><?= (int)$cat['id'] ?></td>
<td class="p-3 border"><?php if(!empty($cat['image'])): ?><img src="/admin/categoryImages/<?= htmlspecialchars($cat['image'],ENT_QUOTES,'UTF-8') ?>" class="h-12 rounded"><?php else: ?>—<?php endif; ?></td>
<td class="p-3 border"><?= htmlspecialchars($cat['name'],ENT_QUOTES,'UTF-8') ?></td>
<td class="p-3 border"><?= htmlspecialchars($cat['parent_name'] ?? '—',ENT_QUOTES,'UTF-8') ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</main>
</div>

<div id="categoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
<div class="bg-white p-6 rounded-xl w-96">
<h2 class="text-xl font-bold mb-4 text-indigo-600">Add Category</h2>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="add_category" value="1">
<label class="block mb-2">Category Name</label>
<input type="text" name="name" maxlength="50" required class="w-full p-2 border rounded mb-4">
<label class="block mb-2">Parent Category</label>
<select name="parent_id" class="w-full p-2 border rounded mb-4">
<option value="">None</option>
<?php foreach($categories as $cat): ?>
<option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['name'],ENT_QUOTES,'UTF-8') ?></option>
<?php endforeach; ?>
</select>
<label class="block mb-2">Category Image</label>
<input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" class="mb-4">
<div class="flex gap-2">
<button type="submit" class="flex-1 bg-indigo-600 text-white py-2 rounded">Save</button>
<button type="button" onclick="closeModal()" class="flex-1 border py-2 rounded">Cancel</button>
</div>
</form>
</div>
</div>

<script>
function openModal(){document.getElementById('categoryModal').classList.remove('hidden');}
function closeModal(){document.getElementById('categoryModal').classList.add('hidden');}
</script>
</body>
</html>
