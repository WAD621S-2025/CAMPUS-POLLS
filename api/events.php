<?php
session_start();
require 'includes/database.php';

// Fetch events from database
$sql = "SELECT * FROM events WHERE event_date >= NOW() ORDER BY event_date ASC";
$result = $conn->query($sql);
$events = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Campus Events - BUZZ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-hover {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .honeycomb-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.05;
            z-index: 0;
            pointer-events: none;
        }
        .alert {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body class="bg-amber-50 dark:bg-gray-900 text-gray-800 dark:text-gray-100 flex flex-col min-h-screen relative transition-colors duration-300 font-inter">
    <div class="honeycomb-bg"></div>

    <header class="bg-gradient-to-r from-yellow-400 to-amber-500 dark:from-amber-600 dark:to-yellow-700 shadow-lg sticky top-0 z-50 transition-colors duration-300">
        <nav class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="index.html" class="flex items-center">
                    <i class="fas fa-university text-2xl text-gray-800 dark:text-gray-100 mr-2"></i>
                    <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100">🐝 BUZZ</h1>
                </a>
                <div class="hidden md:flex items-center space-x-4">
                    <a href="index.html" class="text-gray-800 dark:text-gray-100 hover:bg-white dark:hover:bg-gray-800 hover:bg-opacity-20 px-3 py-2 rounded-md text-sm font-medium transition">Home</a>
                    <a href="polls.html" class="text-gray-800 dark:text-gray-100 hover:bg-white dark:hover:bg-gray-800 hover:bg-opacity-20 px-3 py-2 rounded-md text-sm font-medium transition">Polls</a>
                    <a href="events.php" class="text-gray-800 dark:text-gray-100 font-semibold px-3 py-2 rounded-md text-sm bg-white dark:bg-gray-800 bg-opacity-30">Events</a>
                    <a href="memes.html" class="text-gray-800 dark:text-gray-100 hover:bg-white dark:hover:bg-gray-800 hover:bg-opacity-20 px-3 py-2 rounded-md text-sm font-medium transition">Memes</a>
                    <a href="about.html" class="text-gray-800 dark:text-gray-100 hover:bg-white dark:hover:bg-gray-800 hover:bg-opacity-20 px-3 py-2 rounded-md text-sm font-medium transition">About Us</a>
                </div>
                <div class="flex items-center space-x-4">
                     <button id="theme-toggle" class="theme-toggle p-2 rounded-md bg-white dark:bg-gray-800 bg-opacity-30 hover:bg-opacity-50 transition" title="Toggle Dark Mode">
                        <i class="fas fa-moon dark:hidden text-gray-800"></i>
                        <i class="fas fa-sun hidden dark:inline text-yellow-300"></i>
                    </button>
                    <a href="login.html" id="login-link" class="bg-gray-800 dark:bg-amber-500 text-amber-400 dark:text-gray-900 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-900 dark:hover:bg-amber-600 transition">Login / Sign Up</a>
                    <a href="user_profile.php" id="profile-link">
                        <img id="profile-avatar" class="h-8 w-8 rounded-full object-cover border-2 border-gray-800 dark:border-amber-400" src="https://placehold.co/100x100/fbbf24/1f2937?text=P" alt="User Avatar">
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <main class="flex-grow container mx-auto p-4 sm:p-6 lg:p-8 relative z-10">
        <h2 class="text-3xl font-extrabold mb-8 text-gray-900 dark:text-gray-50 text-center">
            <i class="fas fa-calendar-alt mr-2 text-amber-500"></i> Campus Buzz Events
        </h2>

        <!-- Success/Error Messages -->
        <div id="message-container" class="mb-6"></div>

        <div class="space-y-8">
            <section id="create-event" class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg border-t-4 border-amber-500 dark:border-yellow-500 transition-colors duration-300 card-hover">
                <h3 class="text-2xl font-bold mb-4 text-gray-900 dark:text-gray-50 border-b pb-2 border-amber-200 dark:border-gray-700">
                    <i class="fas fa-plus-circle mr-2 text-amber-500"></i> Post a New Event
                </h3>
                
                <form action="/CAMPUS-POLLS/api/add_event.php" method="POST" class="space-y-4">
                    <div>
                        <input type="text" name="title" id="event-title" placeholder="Event Title (e.g., Annual Science Fair)"
                               class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:text-gray-100" required>
                    </div>

                    <div>
                        <label for="event-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date & Time</label>
                        <input type="datetime-local" name="event_date" id="event-date"
                               class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:text-gray-100" required>
                    </div>
                    
                    <div>
                        <textarea name="description" id="event-description" rows="3"
                                  class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:text-gray-100"
                                  placeholder="Event description and location details..." required></textarea>
                    </div>
                    
                    <div class="flex items-center space-x-4 pt-2">
                        <label for="event-category" class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                            Category: 
                            <select name="category" id="event-category"
                                    class="ml-2 p-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-100 focus:ring-amber-500 focus:border-amber-500"
                                    required>
                                <option value="academic">Academic</option>
                                <option value="social">Social</option>
                                <option value="sports">Sports</option>
                                <option value="workshop">Workshop</option>
                                <option value="other">Other</option>
                            </select>
                        </label>
                        <label class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="is_public" value="1" checked class="form-checkbox h-4 w-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                            <span class="ml-2">Public Event</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full bg-amber-600 text-white font-bold py-3 rounded-lg hover:bg-amber-700 transition duration-150 shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                        <i class="fas fa-calendar-plus mr-2"></i> Publish Event
                    </button>
                </form>
            </section>

            <section id="event-feed" class="space-y-6">
                <div class="flex justify-between items-center pb-2 border-b border-amber-300 dark:border-gray-700">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-50">
                        <i class="fas fa-clipboard-list mr-2 text-amber-500"></i> Upcoming Events
                    </h3>
                    <div class="relative flex items-center space-x-4">
                        <label for="event-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Filter:</label>
                        <select id="event-filter" class="p-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-100 text-sm">
                            <option value="all">All Events</option>
                            <option value="academic">Academic</option>
                            <option value="social">Social</option>
                            <option value="sports">Sports</option>
                            <option value="workshop">Workshop</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <div id="events-container">
                    <?php if (empty($events)): ?>
                        <div class="bg-white dark:bg-gray-800 p-8 rounded-xl shadow-md text-center">
                            <i class="fas fa-calendar-times text-6xl text-gray-400 mb-4"></i>
                            <p class="text-gray-600 dark:text-gray-400 text-lg">No upcoming events found. Be the first to post one!</p>
                        </div>
                    <?php else: ?>
                        <?php 
                        $categoryColors = [
                            'academic' => 'blue',
                            'social' => 'red',
                            'sports' => 'green',
                            'workshop' => 'purple',
                            'other' => 'gray'
                        ];
                        
                        foreach ($events as $event): 
                            $color = $categoryColors[$event['category']] ?? 'gray';
                            $eventDate = new DateTime($event['event_date']);
                            $displayDate = $eventDate->format('M d, Y');
                            $displayTime = $eventDate->format('g:i A');
                        ?>
                        <div class="event-card bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md border-l-4 border-<?php echo $color; ?>-500 transition-colors duration-300 card-hover mb-4" data-category="<?php echo htmlspecialchars($event['category']); ?>">
                            <div class="flex items-start justify-between mb-3">
                                <h4 class="text-xl font-bold text-gray-900 dark:text-gray-50"><?php echo htmlspecialchars($event['title']); ?></h4>
                                <span class="text-xs font-medium text-white bg-<?php echo $color; ?>-500 px-3 py-1 rounded-full shadow-md capitalize"><?php echo htmlspecialchars($event['category']); ?></span>
                            </div>
                            <p class="text-gray-600 dark:text-gray-400 mb-4"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                            
                            <div class="flex items-center space-x-6 text-sm text-gray-700 dark:text-gray-300">
                                <div class="flex items-center">
                                    <i class="fas fa-calendar-day text-amber-500 mr-2"></i>
                                    <span><?php echo $displayDate; ?></span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-clock text-amber-500 mr-2"></i>
                                    <span><?php echo $displayTime; ?></span>
                                </div>
                                <?php if ($event['is_public']): ?>
                                <div class="flex items-center">
                                    <i class="fas fa-globe text-amber-500 mr-2"></i>
                                    <span>Public</span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="flex justify-end mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                                <button class="bg-amber-500 text-white font-semibold py-2 px-4 rounded-lg hover:bg-amber-600 transition shadow-md text-sm">
                                    <i class="fas fa-check mr-1"></i> RSVP
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if (count($events) > 5): ?>
                <div class="text-center p-4">
                    <button class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-6 py-2 rounded-lg text-sm font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition">Load More Events</button>
                </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <footer class="bg-gradient-to-r from-yellow-400 to-amber-500 dark:from-amber-600 dark:to-yellow-700 shadow-inner mt-auto relative z-10 transition-colors duration-300">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-gray-800 dark:text-gray-100">
            <p>&copy; 2025 BUZZ. All Rights Reserved.</p>
             <div class="flex justify-center space-x-4 mt-2">
                <a href="#" class="hover:text-white transition" title="Facebook"><i class="fab fa-facebook"></i></a>
                <a href="#" class="hover:text-white transition" title="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" class="hover:text-white transition" title="Instagram"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <script>
        // Dark mode toggle
        const themeToggle = document.getElementById('theme-toggle');
        const html = document.documentElement;
        const currentTheme = localStorage.getItem('theme') || 'light';
        
        if (currentTheme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            html.classList.add('dark');
        }

        themeToggle.addEventListener('click', () => {
            html.classList.toggle('dark');
            localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
        });

        // Show success/error messages
        function showMessage(type, message) {
            const container = document.getElementById('message-container');
            const bgColor = type === 'success' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            container.innerHTML = `
                <div class="alert ${bgColor} dark:bg-opacity-90 border-l-4 p-4 rounded-lg shadow-md flex items-center">
                    <i class="fas ${icon} mr-3 text-lg"></i>
                    <span>${message}</span>
                    <button onclick="this.parentElement.remove()" class="ml-auto">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
        }

        // Check for URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('success') === '1') {
            showMessage('success', 'Event posted successfully!');
            window.history.replaceState({}, document.title, window.location.pathname);
        } else if (urlParams.get('error')) {
            const errorMessages = {
                'missing_fields': 'Please fill in all required fields.',
                'invalid_date': 'Invalid date format.',
                'database': 'Database error. Please try again.',
                'insert_failed': 'Failed to post event. Please try again.'
            };
            const errorMsg = errorMessages[urlParams.get('error')] || 'An error occurred.';
            showMessage('error', errorMsg);
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        // Filter events by category
        document.getElementById('event-filter').addEventListener('change', function() {
            const filter = this.value;
            const eventCards = document.querySelectorAll('.event-card');
            
            eventCards.forEach(card => {
                const category = card.getAttribute('data-category');
                if (filter === 'all' || category === filter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>