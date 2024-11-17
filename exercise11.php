<?php
session_start();  // Start the session to store the student records

// Initialize students array if not set
if (!isset($_SESSION['students'])) {
    $_SESSION['students'] = [];
}

// Function to find student by ID
function findStudentById($id)
{
    foreach ($_SESSION['students'] as $index => $student) {
        if ($student['ID'] == $id) {
            return $index;
        }
    }
    return -1;  // Return -1 if student not found
}

// Handle form submission (both for adding and editing)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $math = $_POST['math'];
    $english = $_POST['english'];
    $science = $_POST['science'];

    $studentIndex = findStudentById($id);

    // If student exists, update the record
    if ($studentIndex !== -1) {
        $_SESSION['students'][$studentIndex] = [
            'ID' => $id,
            'Name' => $name,
            'Grades' => [
                'Math' => $math,
                'English' => $english,
                'Science' => $science
            ]
        ];
    } else {
        // If student does not exist, add a new record
        $_SESSION['students'][] = [
            'ID' => $id,
            'Name' => $name,
            'Grades' => [
                'Math' => $math,
                'English' => $english,
                'Science' => $science
            ]
        ];
    }
}

// Function to calculate averages and top scorers
function calculateClassAverages()
{
    $totals = ['Math' => 0, 'English' => 0, 'Science' => 0];
    $totalStudents = count($_SESSION['students']);
    foreach ($_SESSION['students'] as $student) {
        foreach ($totals as $subject => $total) {
            $totals[$subject] += $student['Grades'][$subject];
        }
    }

    return [
        'Math' => $totals['Math'] / $totalStudents,
        'English' => $totals['English'] / $totalStudents,
        'Science' => $totals['Science'] / $totalStudents
    ];
}

function topScorers()
{
    $topScorers = [];
    foreach (['Math', 'English', 'Science'] as $subject) {
        $topScore = -1;
        $topStudent = null;
        foreach ($_SESSION['students'] as $student) {
            if ($student['Grades'][$subject] > $topScore) {
                $topScore = $student['Grades'][$subject];
                $topStudent = $student['Name'];
            }
        }
        $topScorers[$subject] = $topStudent;
    }
    return $topScorers;
}

function calculateStudentAverages()
{
    $averages = [];
    $classAverage = array_sum(calculateClassAverages()) / 3;
    foreach ($_SESSION['students'] as $student) {
        $avgScore = array_sum($student['Grades']) / count($student['Grades']);
        $averages[$student['ID']] = [
            'average' => $avgScore,
            'status' => $avgScore >= $classAverage ? 'Above Average' : 'Below Average'
        ];
    }
    return $averages;
}

// If "Delete" is clicked, remove the student record
if (isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];
    foreach ($_SESSION['students'] as $index => $student) {
        if ($student['ID'] == $deleteId) {
            unset($_SESSION['students'][$index]);
            $_SESSION['students'] = array_values($_SESSION['students']); // Re-index array
            break;
        }
    }
}

// If "Edit" is clicked, populate the form with the student's data
$editStudent = null;
if (isset($_GET['edit'])) {
    $editId = $_GET['edit'];
    $editIndex = findStudentById($editId);
    if ($editIndex !== -1) {
        $editStudent = $_SESSION['students'][$editIndex];
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Student Gradebook</title>
</head>

<body>
    <h2>Student Gradebook</h2>

    <!-- Form to Add or Edit Student -->
    <form action="" method="POST">
        <label for="name">Full Name: </label>
        <input type="text" id="name" name="name" value="<?= $editStudent ? $editStudent['Name'] : '' ?>" required /><br />

        <label for="ID">ID Number: </label>
        <input type="number" name="id" id="ID" value="<?= $editStudent ? $editStudent['ID'] : '' ?>" required /><br />

        <label for="math">Math Score (0-100): </label>
        <input type="number" name="math" id="math" value="<?= $editStudent ? $editStudent['Grades']['Math'] : '' ?>" required /><br />

        <label for="english">English Score (0-100): </label>
        <input type="number" name="english" id="english" value="<?= $editStudent ? $editStudent['Grades']['English'] : '' ?>" required /><br />

        <label for="science">Science Score (0-100): </label>
        <input type="number" name="science" id="science" value="<?= $editStudent ? $editStudent['Grades']['Science'] : '' ?>" required /><br />

        <input type="submit" value="Submit" />
    </form>

    <!-- Display student records -->
    <h3>Student Records</h3>
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Math</th>
                <th>English</th>
                <th>Science</th>
                <th>Average</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($_SESSION['students'] as $student): ?>
                <tr>
                    <td><?= $student['ID'] ?></td>
                    <td><?= $student['Name'] ?></td>
                    <td><?= $student['Grades']['Math'] ?></td>
                    <td><?= $student['Grades']['English'] ?></td>
                    <td><?= $student['Grades']['Science'] ?></td>
                    <td>
                        <?php
                        $average = array_sum($student['Grades']) / count($student['Grades']);
                        echo round($average, 2);
                        ?>
                    </td>
                    <td>
                        <a href="?edit=<?= $student['ID'] ?>">Edit</a>
                        <a href="?delete=<?= $student['ID'] ?>">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Analytics -->
    <h3>Analytics</h3>
    <p><strong>Average Score per Subject:</strong></p>
    <ul>
        <?php $classAverages = calculateClassAverages(); ?>
        <li>Math: <?= round($classAverages['Math'], 2) ?></li>
        <li>English: <?= round($classAverages['English'], 2) ?></li>
        <li>Science: <?= round($classAverages['Science'], 2) ?></li>
    </ul>

    <p><strong>Top Scorer in Each Subject:</strong></p>
    <ul>
        <?php $topScorers = topScorers(); ?>
        <li>Math: <?= $topScorers['Math'] ?></li>
        <li>English: <?= $topScorers['English'] ?></li>
        <li>Science: <?= $topScorers['Science'] ?></li>
    </ul>

    <p><strong>Student Performance:</strong></p>
    <ul>
        <?php $studentAverages = calculateStudentAverages(); ?>
        <?php foreach ($studentAverages as $id => $data): ?>
            <li>Student ID <?= $id ?>: Average Score: <?= round($data['average'], 2) ?> - <?= $data['status'] ?></li>
        <?php endforeach; ?>
    </ul>
</body>

</html>