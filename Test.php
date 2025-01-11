<div>
                    <h2 class="Am">Assignments</h2>
                    <?php while ($assignment = $assignments->fetch_assoc()): ?>
                    <div class="mCard">
                        <h3><?php echo htmlspecialchars($assignment['title']); ?></h3>
                        <p><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                        <p><strong>Due Date:</strong> <?php echo $assignment['dueDate']; ?></p>

                        <!-- View Submissions Button (Only for Teachers) -->
                        <?php if ($role === 'Teacher'): ?>
                        <div style="text-align: right; margin-bottom: 10px;">
                            <button class="VSViewSubmissionsBtn"
                                data-assignment-id="<?php echo $assignment['assignmentID']; ?>"
                                style="background-color: #4CAF50; color: white; padding: 10px; border: none; border-radius: 5px; cursor: pointer;">
                                View Submissions
                            </button>
                        </div>
                        <?php endif; ?>

                        <!-- Assignment Files -->
                        <h4>Assignment Files:</h4>
                        <ul>
                            <?php 
                $files = fetchAssignmentFiles($assignment['assignmentID']);
                while ($file = $files->fetch_assoc()):
            ?>
                            <li>
                                <a href="download_file.php?fileID=<?php echo $file['fileID']; ?>&fileType=assignment">
                                    <?php echo htmlspecialchars($file['fileName']); ?>
                                </a>
                            </li>
                            <?php endwhile; ?>
                        </ul>

                        <!-- Student Submission Form -->
                        <?php if ($role == 'Student'): ?>
                        <div class="assignment-card"
                            style="border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 5px;">
                            <h4>Assignment: <?php echo htmlspecialchars($assignment['title']); ?></h4>
                            <p>Deadline: <?php echo htmlspecialchars($assignment['dueDate']); ?></p>

                            <?php if (!$submission): ?>
                            <?php if (isBeforeDeadline($assignment['assignmentID'])): ?>
                            <form action="" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="assignmentID"
                                    value="<?php echo $assignment['assignmentID']; ?>">
                                <label for="file">Submit Assignment:</label>
                                <input type="file" name="file" required><br>
                                <button type="submit" name="submitAssignment">Submit Assignment</button>
                            </form>
                            <?php else: ?>
                            <p style="color: red;">The deadline has passed. You can still submit, but it will be marked
                                as late.</p>
                            <form action="" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="assignmentID"
                                    value="<?php echo $assignment['assignmentID']; ?>">
                                <label for="file">Submit Assignment:</label>
                                <input type="file" name="file" required><br>
                                <button type="submit" name="submitAssignment">Submit Assignment</button>
                            </form>
                            <?php endif; ?>
                            <?php else: ?>
                            <p style="color: green; font-weight: bold;">You have already submitted this assignment.</p>
                            <?php if ($submission['isLate']): ?>
                            <p style="color: orange;">Status: Late Submitted</p>
                            <?php endif; ?>
                            <div
                                style="background-color: #f9f9f9; padding: 10px; border-radius: 5px; margin-top: 10px;">
                                <p><strong>Submitted File:</strong> <a
                                        href="download.php?id=<?php echo $submission['submissionID']; ?>">Download</a>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Comments Section -->
                        <h4>Comments:</h4>
                        <div class="comment-box">
                            <?php 
                $comments = fetchComments($assignment['assignmentID']);
                while ($comment = $comments->fetch_assoc()):
            ?>
                            <p><strong>
                                    <?php 
                        if ($comment['userID'] == $userID) {
                            echo 'You';
                        } else {
                            $stmt = $conn->prepare("SELECT name FROM Users WHERE userID = ?");
                            $stmt->bind_param("i", $comment['userID']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $commenter = $result->fetch_assoc();
                            echo htmlspecialchars($commenter['name']);
                        }
                    ?>:
                                </strong>
                                <?php echo htmlspecialchars($comment['comment']); ?>
                                <small>(<?php echo $comment['commentDate']; ?>)</small>
                            </p>
                            <?php endwhile; ?>
                        </div>

                        <!-- Add Comment Section -->
                        <?php if ($role == 'Teacher' || $role == 'Student'): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="assignmentID" value="<?php echo $assignment['assignmentID']; ?>">
                            <textarea name="comment" placeholder="Add your comment here..." required></textarea>
                            <button type="submit" name="addComment">Add Comment</button>
                        </form>
                        <?php endif; ?>

                        <!-- Delete Assignment Button -->
                        <?php if ($role === 'Teacher'): ?>
                        <form method="POST">
                            <input type="hidden" name="assignment_id"
                                value="<?php echo $assignment['assignmentID']; ?>">
                            <button type="submit" name="delete_assignment">Delete Assignment</button>
                        </form>
                        <?php endif; ?>

                    </div>
                    <?php endwhile; ?>
                </div>