<?php
session_start();
$koneksi = new mysqli("localhost", "root", "", "todo_app");
if ($koneksi->connect_error) {
    die("Connection failed: " . $koneksi->connect_error);
}

if (!isset($_SESSION["user_id"])) {
    die("Error: User is not logged in.");
}
$user_id = $_SESSION["user_id"];

if (isset($_GET['logout'])) {
    session_start();
    session_destroy();
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["addTask"])) {
        $stmt = $koneksi->prepare("INSERT INTO tasks (user_id, task_name, priority, due_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $_POST["task"], $_POST["priority"], $_POST["dueDate"]);
        $stmt->execute();
        $stmt->close();
    }
    if (isset($_POST["deleteTask"])) {
        $stmt = $koneksi->prepare("DELETE FROM subtasks WHERE task_id = ?");
        $stmt->bind_param("i", $_POST["task_id"]);
        $stmt->execute();
        $stmt = $koneksi->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->bind_param("i", $_POST["task_id"]);
        $stmt->execute();
        $stmt->close();
    }
    if (isset($_POST["editTask"])) {
        $stmt = $koneksi->prepare("UPDATE tasks SET task_name = ?, priority = ?, due_date = ? WHERE id = ?");
        $stmt->bind_param("sssi", $_POST["task"], $_POST["priority"], $_POST["dueDate"], $_POST["task_id"]);
        $stmt->execute();
        $stmt->close();
    }
    if (isset($_POST["addSubtask"])) {
        $stmt = $koneksi->prepare("INSERT INTO subtasks (task_id, subtask_name, completed) VALUES (?, ?, 0)");
        $stmt->bind_param("is", $_POST["task_id"], $_POST["subtask"]);
        $stmt->execute();
        $stmt->close();
    }
    if (isset($_POST["toggleSubtask"])) {
        $stmt = $koneksi->prepare("UPDATE subtasks SET completed = NOT completed WHERE id = ?");
        $stmt->bind_param("i", $_POST["subtask_id"]);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Cek tenggat waktu dan kirim peringatan
$currentDate = date('Y-m-d');
$tomorrowDate = date('Y-m-d', strtotime('+1 day'));

$stmt = $koneksi->prepare("SELECT * FROM tasks WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tasks = $stmt->get_result();

$warnings = []; // Array untuk menyimpan peringatan tugas

while ($task = $tasks->fetch_assoc()) {
    // Pastikan $task tidak null dan memiliki kolom yang diperlukan
    if ($task === null || !isset($task['due_date'], $task['task_name'], $task['reminder_sent'])) {
        continue; // Lewati iterasi jika tidak ada data yang diperlukan
    }

    // Cek jika tugas sudah lewat tenggat waktu
    if ($task['due_date'] < $currentDate && !$task['reminder_sent']) {
        $warnings[] = "⚠️ Task <strong>{$task['task_name']}</strong> is overdue!";
        $updateStmt = $koneksi->prepare("UPDATE tasks SET reminder_sent = 1 WHERE id = ?");
        $updateStmt->bind_param("i", $task['id']);
        $updateStmt->execute();
        $updateStmt->close();
    }

    // Cek jika tugas jatuh tempo besok
    if ($task['due_date'] == $tomorrowDate && !$task['reminder_sent']) {
        $warnings[] = "⏳ Task <strong>{$task['task_name']}</strong> is due tomorrow!";
        $updateStmt = $koneksi->prepare("UPDATE tasks SET reminder_sent = 1 WHERE id = ?");
        $updateStmt->bind_param("i", $task['id']);
        $updateStmt->execute();
        $updateStmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do List</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .completed {
            text-decoration: line-through;
            color: gray;
        }
        .overdue {
            color: red; /* Warna merah untuk tugas yang sudah lewat tenggat waktu */
            text-decoration: line-through; /* Mencoret teks */
        }
        .task-container {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .task-actions {
            display: flex;
            gap: 5px;
        }
        .subtasks {
            margin-left: 20px;
        }
        .edit-form {
            display: none;
            margin-top: 5px;
        }
        .logout-btn {
            float: right;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>To-Do List</h1>
        <div class="input-container">
            <form method="POST">
                <input type="text" name="task" placeholder="Add a new task..." required>
                <select name="priority">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
                <input type="date" name="dueDate" required>
                <button type="submit" name="addTask">Add Task</button>
            </form>
        </div>

        <?php
        $stmt = $koneksi->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY due_date ASC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        while ($task = $tasks->fetch_assoc()) {
            if ($task === null || !isset($task['task_name'], $task['due_date'], $task['priority'])) {
                continue; // Lewati jika tidak ada data yang diperlukan
            }

            $subCountStmt = $koneksi->prepare("SELECT COUNT(*) as total, SUM(completed) as done FROM subtasks WHERE task_id = ?");
            $subCountStmt->bind_param("i", $task['id']);
            $subCountStmt->execute();
            $subCountResult = $subCountStmt->get_result()->fetch_assoc();
            $subCountStmt->close();
            
            $allCompleted = ($subCountResult['total'] > 0 && $subCountResult['total'] == $subCountResult['done']);
            $taskClass = $allCompleted ? "completed" : "";

            // Cek apakah tenggat waktu telah terlewati
            if ($task['due_date'] < $currentDate) {
                $taskClass .= " overdue"; // Tambahkan kelas overdue jika sudah lewat
            }

            echo "<div class='task-container'>";
            echo "<p class='$taskClass'><strong>" . htmlspecialchars($task['task_name']) . "</strong> (Priority: " . htmlspecialchars($task['priority']) . ", Due: " . htmlspecialchars($task['due_date']) . ")</p>";

            // Tambahkan peringatan tugas yang jatuh tempo besok
            if ($task['due_date'] == $tomorrowDate) {
                echo "<p style='color: orange; font-weight: bold;'>⏳ Task <strong>" . htmlspecialchars($task['task_name']) . "</strong> is due tomorrow!</p>";
            }

            echo "<div class='task-actions'>";
            echo "<button onclick='toggleEditForm(".$task['id'].")'>Edit</button>";

            echo "<form method='POST' style='display:inline;'>
                    <input type='hidden' name='task_id' value='".$task['id']."'>
                    <button type='submit' name='deleteTask'>Delete</button>
                </form>";
            echo "</div>";
            
            echo "</div>";

            echo "<div id='editForm".$task['id']."' class='edit-form'>
                    <form method='POST'>
                        <input type='hidden' name='task_id' value='".$task['id']."'>
                        <input type='text' name='task' value='".htmlspecialchars($task['task_name'])."' required>
                        <select name='priority'>
                            <option value='low' ".($task['priority']=='low' ? "selected" : "").">Low</option>
                            <option value='medium' ".($task['priority']=='medium' ? "selected" : "").">Medium</option>
                            <option value='high' ".($task['priority']=='high' ? "selected" : "").">High</option>
                        </select>
                        <input type='date' name='dueDate' value='".htmlspecialchars($task['due_date'])."' required>
                        <button type='submit' name='editTask'>Save</button>
                    </form>
                </div>";

            echo "<div class='add-subtask'>
                    <form method='POST'>
                        <input type='hidden' name='task_id' value='".$task['id']."'>
                        <input type='text' name='subtask' placeholder='Add subtask...'>
                        <button type='submit' name='addSubtask'>Add Subtask</button>
                    </form>
                </div>";

            $substmt = $koneksi->prepare("SELECT * FROM subtasks WHERE task_id = ?");
            $substmt->bind_param("i", $task['id']);
            $substmt->execute();
            $subtasks = $substmt->get_result();
            echo "<ul class='subtasks'>";
            while ($subtask = $subtasks->fetch_assoc()) {
                $checked = $subtask['completed'] ? "checked" : "";
                echo "<li>
                        <form method='POST' style='display:inline;'>
                            <input type='hidden' name='subtask_id' value='".$subtask['id']."'>
                            <input type='checkbox' onchange='this.form.submit()' ".$checked.">
                            ".htmlspecialchars($subtask['subtask_name'])."
                            <input type='hidden' name='toggleSubtask' value='1'>
                        </form>
                    </li>";
            }
            echo "</ul>";
        }
        ?>
    </div>
    <!-- Tombol Logout dengan Form GET -->
    <form method="GET">
        <button type="submit" name="logout" value="true">Logout</button>
    </form>

    <script>
        function toggleEditForm(taskId) {
            var form = document.getElementById("editForm" + taskId);
            form.style.display = (form.style.display === "none" || form.style.display === "") ? "block" : "none";
        }
    </script>
</body>
</html>