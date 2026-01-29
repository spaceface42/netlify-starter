<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$dbFile = __DIR__ . '/database.sqlite';
$uploadBase = __DIR__ . '/uploads/projects';

$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = ON');

// Helpers
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function slugify(string $s): string { return trim(preg_replace('~[^a-z0-9]+~', '-', strtolower($s)), '-'); }
function ensureDir(string $dir): void { if(!is_dir($dir)) mkdir($dir, 0755, true); }
function uploadImage(array $file, string $dir): ?string {
    if($file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if(!in_array($ext, ['jpg','jpeg','png','webp'])) return null;
    ensureDir($dir);
    $name = uniqid('', true) . '.' . $ext;
    return move_uploaded_file($file['tmp_name'], "$dir/$name") ? $name : null;
}

/* -------------------
   ACTIONS
------------------- */
$action = $_POST['action'] ?? $_GET['action'] ?? null;

/* CREATE PROJECT */
if($action==='create') {
    $title = trim($_POST['title']);
    $slug = slugify($_POST['slug'] ?: $title);
    $pdo->prepare("INSERT INTO projects (title, slug, description, client, year, published) VALUES (?,?,?,?,?,?)")
        ->execute([$title,$slug,$_POST['description'],$_POST['client'],(int)$_POST['year'],isset($_POST['published'])?1:0]);
    $id = (int)$pdo->lastInsertId();
    $dir = "$uploadBase/$slug";

    if(!empty($_FILES['cover_image']['name'])) {
        if($img = uploadImage($_FILES['cover_image'], $dir)){
            $pdo->prepare("UPDATE projects SET cover_image=? WHERE id=?")->execute([$img,$id]);
        }
    }
    if(!empty($_FILES['images']['name'][0])) {
        foreach($_FILES['images']['tmp_name'] as $i => $tmp) {
            $file = [
                'name'=>$_FILES['images']['name'][$i],
                'tmp_name'=>$tmp,
                'error'=>$_FILES['images']['error'][$i]
            ];
            if($img = uploadImage($file, $dir)){
                $pdo->prepare("INSERT INTO project_images (project_id, filename) VALUES (?,?)")
                    ->execute([$id, $img]);
            }
        }
    }
    header('Location: admin.php'); exit;
}

/* UPDATE PROJECT */
if($action==='update') {
    $id = (int)$_POST['id'];
    $oldSlug = $pdo->query("SELECT slug FROM projects WHERE id=$id")->fetchColumn();
    $newSlug = slugify($_POST['slug'] ?: $_POST['title']);
    if($oldSlug !== $newSlug && is_dir("$uploadBase/$oldSlug")) rename("$uploadBase/$oldSlug","$uploadBase/$newSlug");

    $pdo->prepare("UPDATE projects SET title=?,slug=?,description=?,client=?,year=?,published=?,updated_at=datetime('now') WHERE id=?")
        ->execute([$_POST['title'],$newSlug,$_POST['description'],$_POST['client'],(int)$_POST['year'],isset($_POST['published'])?1:0,$id]);

    $dir = "$uploadBase/$newSlug";

    if(!empty($_FILES['cover_image']['name'])){
        if($img = uploadImage($_FILES['cover_image'], $dir)){
            $pdo->prepare("UPDATE projects SET cover_image=? WHERE id=?")->execute([$img,$id]);
        }
    }

    if(!empty($_FILES['images']['name'][0])){
        foreach($_FILES['images']['tmp_name'] as $i => $tmp){
            $file = ['name'=>$_FILES['images']['name'][$i],'tmp_name'=>$tmp,'error'=>$_FILES['images']['error'][$i]];
            if($img = uploadImage($file, $dir)){
                $pdo->prepare("INSERT INTO project_images (project_id, filename) VALUES (?,?)")
                    ->execute([$id,$img]);
            }
        }
    }

    header("Location: admin.php?edit=$id"); exit;
}

/* DELETE IMAGE */
if($action==='delete_image'){
    $id = (int)$_GET['id'];
    $img = $pdo->query("SELECT pi.filename,p.slug FROM project_images pi JOIN projects p ON p.id=pi.project_id WHERE pi.id=$id")->fetch();
    if($img){ @unlink("$uploadBase/{$img['slug']}/{$img['filename']}"); $pdo->prepare("DELETE FROM project_images WHERE id=?")->execute([$id]); }
    header('Location: admin.php'); exit;
}

/* DELETE PROJECT */
if($action==='delete_project'){
    $id = (int)$_GET['id'];
    $slug = $pdo->query("SELECT slug FROM projects WHERE id=$id")->fetchColumn();
    if($slug && is_dir("$uploadBase/$slug")){ foreach(glob("$uploadBase/$slug/*") as $f) @unlink($f); rmdir("$uploadBase/$slug"); }
    $pdo->prepare("DELETE FROM projects WHERE id=?")->execute([$id]);
    header('Location: admin.php'); exit;
}

/* UPDATE IMAGE META */
if($action==='update_image_meta'){
    $pdo->prepare("UPDATE project_images SET alt_text=?,sort_order=? WHERE id=?")
        ->execute([$_POST['alt_text'],(int)$_POST['sort_order'],(int)$_POST['id']]);
    exit;
}

/* FETCH PROJECTS */
$projects = $pdo->query("SELECT * FROM projects ORDER BY sort_order ASC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : null;

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Portfolio Admin</title>
<style>
body{font-family:system-ui;background:#f3f3f3;padding:2rem;}
.project{background:#fff;padding:1rem;margin-bottom:1rem;border-radius:6px;}
.project form input, .project form textarea{width:100%;margin-bottom:.5rem;padding:.4rem;}
.actions a{margin-right:1rem;}
.images{display:flex;flex-wrap:wrap;gap:10px;margin-top:10px;}
.image{width:150px;text-align:center;}
.image img{width:100%;border-radius:4px;}
</style>
<script>
function enableSorting(container){
    let dragged=null;
    container.querySelectorAll('.image').forEach(el=>{
        el.draggable=true;
        el.addEventListener('dragstart',()=>dragged=el);
        el.addEventListener('dragover',e=>e.preventDefault());
        el.addEventListener('drop',()=>{
            if(dragged && dragged!==el){ el.parentNode.insertBefore(dragged,el);}
            [...container.children].forEach((img,i)=>{
                fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
                    body:`action=update_image_meta&id=${img.dataset.id}&sort_order=${i}&alt_text=${encodeURIComponent(img.querySelector('input').value)}`});
            });
        });
    });
}
</script>
</head>
<body>

<h1>Portfolio Admin</h1>

<h2>Create Project</h2>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="action" value="create">
<input name="title" placeholder="Title" required>
<input name="slug" placeholder="Slug (optional)">
<textarea name="description" placeholder="Description"></textarea>
<input name="client" placeholder="Client">
<input name="year" type="number" placeholder="Year">
<label><input type="checkbox" name="published"> Published</label>
<input type="file" name="cover_image">
<input type="file" name="images[]" multiple>
<button>Create</button>
</form>

<hr>

<?php foreach($projects as $p): ?>
<div class="project">
<strong><?= e($p['title']) ?></strong>
<div class="actions">
  <a href="?edit=<?= $p['id'] ?>">Edit</a>
  <a href="?action=delete_project&id=<?= $p['id'] ?>" onclick="return confirm('Delete project?')">Delete</a>
</div>

<?php if($editId === (int)$p['id']): ?>

<!-- Cover Image Preview -->
<?php if($p['cover_image'] && file_exists("$uploadBase/{$p['slug']}/{$p['cover_image']}")): ?>
<img src="uploads/projects/<?= e($p['slug']) ?>/<?= e($p['cover_image']) ?>" style="max-width:200px;margin-bottom:10px">
<?php endif; ?>

<!-- Edit Form -->
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="action" value="update">
<input type="hidden" name="id" value="<?= $p['id'] ?>">

<input name="title" value="<?= e($p['title']) ?>" placeholder="Title">
<input name="slug" value="<?= e($p['slug']) ?>" placeholder="Slug">
<textarea name="description" placeholder="Description"><?= e($p['description']) ?></textarea>
<input name="client" value="<?= e($p['client']) ?>" placeholder="Client">
<input name="year" value="<?= e($p['year']) ?>" placeholder="Year">
<label><input type="checkbox" name="published" <?= $p['published']?'checked':'' ?>> Published</label>

<input type="file" name="cover_image">
<input type="file" name="images[]" multiple>
<button>Save</button>
</form>

<!-- Content Images -->
<h3>Content Images</h3>
<div class="images" id="images-<?= $p['id'] ?>">
<?php
$imgs = $pdo->prepare("SELECT * FROM project_images WHERE project_id=? ORDER BY sort_order ASC, id ASC");
$imgs->execute([$p['id']]);
foreach($imgs->fetchAll(PDO::FETCH_ASSOC) as $img):
    $imgPath = "uploads/projects/{$p['slug']}/{$img['filename']}";
    if(!file_exists($imgPath)) continue;
?>
<div class="image" data-id="<?= $img['id'] ?>">
<img src="<?= e($imgPath) ?>" alt="<?= e($img['alt_text']) ?>">
<input value="<?= e($img['alt_text']) ?>" placeholder="Alt text" onblur="enableSorting(this.parentNode.parentNode)">
<br><a href="?action=delete_image&id=<?= $img['id'] ?>" onclick="return confirm('Delete this image?')">Delete</a>
</div>
<?php endforeach; ?>
</div>
<script>enableSorting(document.getElementById('images-<?= $p['id'] ?>'));</script>

<?php endif; ?>

</div>
<?php endforeach; ?>

</body>
</html>
