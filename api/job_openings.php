        <?php
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        require_once __DIR__ . '/../config.php';

        $conn = get_db();

        // Check if table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'job_openings'");
        if (!$tableCheck || $tableCheck->num_rows === 0) {
            // Table doesn't exist yet — return empty list gracefully
            echo json_encode(['success' => true, 'jobs' => []]);
            $conn->close();
            exit;
        }

        $result = $conn->query(
            "SELECT id, title, job_type, badge_type, duration, location, salary_label,
                    description, is_featured, is_active
            FROM job_openings
            WHERE is_active = 1
            ORDER BY sort_order ASC, id DESC"
        );

        $jobs = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row['is_featured'] = (bool)$row['is_featured'];
                $jobs[] = $row;
            }
        }

        $conn->close();
        echo json_encode(['success' => true, 'jobs' => $jobs]);
