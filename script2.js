function addTask() {
    var taskInput = document.getElementById("task");
    var priorityInput = document.getElementById("priority");
    var dueDateInput = document.getElementById("dueDate");
    var taskText = taskInput.value.trim();
    var priority = priorityInput.value;
    var dueDate = dueDateInput.value;

    if (taskText === '' || dueDate === '') {
        alert("Task name and due date cannot be empty!");
        return;
    }

    var taskList = document.getElementById("taskList");
    var newTask = document.createElement("li");
    newTask.classList.add("task-item");
    newTask.setAttribute("data-deadline", dueDate);


    newTask.innerHTML = `
        <div class="task-header">
            <span class="task-content">${taskText} (Priority: ${priority}, Due: ${dueDate})</span>
            <div class="task-actions">
                <button class="edit-btn" onclick="editTask(this)">Edit</button>
                <button class="delete-btn" onclick="removeTask(this)">Delete</button>
            </div>
        </div>
        <div class="subtask-container">
            <input type="text" class="subtask-input" placeholder="Add subtask...">
            <button onclick="addSubtask(this)">Add Subtask</button>
            <ul class="subtask-list"></ul>
        </div>
        <hr>
    `;

    taskList.appendChild(newTask);
    taskInput.value = '';
    priorityInput.value = 'low';
    dueDateInput.value = '';

    checkDeadlines();
}

function checkTaskStatus(checkbox) {
    var taskItem = checkbox.closest(".task-item");
    var subtaskList = taskItem.querySelector(".subtask-list");
    var allChecked = [...subtaskList.querySelectorAll(".subtask-checkbox")].every(cb => cb.checked);

    if (allChecked) {
        taskItem.querySelector(".task-content").style.textDecoration = "line-through";
    } else {
        taskItem.querySelector(".task-content").style.textDecoration = "none";
    }
}

function addSubtask(button) {
    var subtaskInput = button.previousElementSibling;
    var subtaskText = subtaskInput.value.trim();
    if (subtaskText === '') {
        alert("Subtask cannot be empty!");
        return;
    }

    var subtaskList = button.nextElementSibling;
    var subtaskItem = document.createElement("li");

    subtaskItem.innerHTML = `
        <label class="subtask-label">
            <input type="checkbox" class="subtask-checkbox" onclick="checkTaskStatus(this)">
            <span>${subtaskText}</span>
        </label>
    `;

    subtaskList.appendChild(subtaskItem);
    subtaskInput.value = '';
}

function editTask(button) {
    let taskItem = button.closest(".task-item");
    let taskContent = taskItem.querySelector(".task-content").textContent;
    let priorityText = taskContent.match(/Priority: (\w+)/)[1];
    let dueDateText = taskContent.match(/Due: (\d{4}-\d{2}-\d{2})/)[1];
    let taskName = taskContent.split(" (Priority")[0];
    let subtaskList = taskItem.querySelector(".subtask-list").innerHTML;

    taskItem.innerHTML = `
        <input type="text" value="${taskName}" class="edit-title">
        <select class="edit-priority">
            <option value="low" ${priorityText === "low" ? "selected" : ""}>Low</option>
            <option value="medium" ${priorityText === "medium" ? "selected" : ""}>Medium</option>
            <option value="high" ${priorityText === "high" ? "selected" : ""}>High</option>
        </select>
        <input type="date" value="${dueDateText}" class="edit-date">
        <button onclick="saveTask(this)" class="save-btn">Save</button>
        <ul class="subtask-list">${subtaskList}</ul>
    `;
}

function saveTask(button) {
    let taskItem = button.closest(".task-item");
    let newTitle = taskItem.querySelector(".edit-title").value;
    let newPriority = taskItem.querySelector(".edit-priority").value;
    let newDate = taskItem.querySelector(".edit-date").value;
    let subtaskList = taskItem.querySelector(".subtask-list").innerHTML;

    taskItem.innerHTML = `
        <div class="task-header">
            <span class="task-content">${newTitle} (Priority: ${newPriority}, Due: ${newDate})</span>
            <div class="task-actions">
                <button class="edit-btn" onclick="editTask(this)">Edit</button>
                <button class="delete-btn" onclick="removeTask(this)">Delete</button>
            </div>
        </div>
        <div class="subtask-container">
            <input type="text" class="subtask-input" placeholder="Add subtask...">
            <button onclick="addSubtask(this)">Add Subtask</button>
            <ul class="subtask-list">${subtaskList}</ul>
        </div>
    `;
}

function removeTask(button) {
    let taskItem = button.closest(".task-item");
    taskItem.remove();
}

function searchTask() {
    let input = document.getElementById("searchTask").value.toLowerCase();
    let tasks = document.querySelectorAll(".task-item");

    tasks.forEach(task => {
        let taskText = task.querySelector(".task-content").textContent.toLowerCase();
        task.style.display = taskText.includes(input) ? "block" : "none";
    });
}

document.addEventListener("DOMContentLoaded", function() {
    const tasks = document.querySelectorAll('.task-container'); // Ganti dengan selector yang sesuai

    tasks.forEach(task => {
        const dueDate = new Date(task.getAttribute('data-due-date')); // Ambil tanggal tenggat dari atribut data
        const currentDate = new Date();

        if (dueDate < currentDate) {
            task.classList.add('overdue'); // Tambahkan kelas overdue
        }
    });
});


function logout() {
    localStorage.removeItem("userToken"); // Hapus data login di local storage
    sessionStorage.removeItem("userToken"); // Hapus session jika digunakan
    window.location.href = "index.php"; // Redirect ke halaman login
}
