// ============================================
// POLLS FUNCTIONALITY
// ============================================

// Polls data stored in memory
let pollsData = [
    {
        id: 1,
        question: 'Best study spot on campus?',
        options: [
            { text: 'The Library', votes: 45 },
            { text: 'Student Cafe', votes: 32 },
            { text: 'The Labs', votes: 23 }
        ],
        hasVoted: false,
        showResults: false
    },
    {
        id: 2,
        question: 'Favorite on-campus food shop?',
        options: [
            { text: 'The Tuck-shop', votes: 120 },
            { text: 'Kasi Burger', votes: 85 },
            { text: 'Hotspot', votes: 95 }
        ],
        hasVoted: false,
        showResults: true
    }
];

let chartInstances = {};

// Render all polls
function renderPolls() {
    const pollsContainer = document.querySelector('.space-y-6');
    if (!pollsContainer) return;
    
    pollsContainer.innerHTML = '';
    
    pollsData.forEach(poll => {
        const pollCard = createPollCard(poll);
        pollsContainer.appendChild(pollCard);
    });
}

// Create a poll card element
function createPollCard(poll) {
    const card = document.createElement('div');
    card.className = 'border border-gray-200 p-4 rounded-lg';
    card.dataset.pollId = poll.id;
    
    if (poll.showResults || poll.hasVoted) {
        // Show results view with chart
        card.innerHTML = `
            <p class="font-semibold mb-4">${poll.question}</p>
            <div class="flex justify-center items-center mb-4">
                <div class="w-full max-w-xs">
                    <canvas id="pollChart-${poll.id}"></canvas>
                </div>
            </div>
            <div class="space-y-2 text-sm">
                ${poll.options.map(option => {
                    const totalVotes = poll.options.reduce((sum, opt) => sum + opt.votes, 0);
                    const percentage = totalVotes > 0 ? ((option.votes / totalVotes) * 100).toFixed(1) : 0;
                    return `
                        <div class="flex justify-between items-center">
                            <span>${option.text}</span>
                            <span class="text-gray-600">${option.votes} votes (${percentage}%)</span>
                        </div>
                    `;
                }).join('')}
            </div>
        `;
        
        // Render chart after adding to DOM
        setTimeout(() => renderPollChart(poll), 0);
    } else {
        // Show voting view with buttons
        card.innerHTML = `
            <p class="font-semibold">${poll.question}</p>
            <div class="mt-2 space-y-2 poll-options">
                ${poll.options.map((option, index) => `
                    <button class="vote-btn w-full text-left p-2 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors" data-option-index="${index}">
                        ${option.text}
                    </button>
                `).join('')}
            </div>
            <button class="view-results-btn mt-3 text-sm text-indigo-600 hover:underline">View Results</button>
        `;
        
        // Add vote button listeners
        const voteButtons = card.querySelectorAll('.vote-btn');
        voteButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const optionIndex = parseInt(e.target.dataset.optionIndex);
                handleVote(poll.id, optionIndex);
            });
        });
        
        // Add view results button listener
        const viewResultsBtn = card.querySelector('.view-results-btn');
        viewResultsBtn.addEventListener('click', () => {
            poll.showResults = true;
            renderPolls();
        });
    }
    
    return card;
}

// Handle voting on a poll
function handleVote(pollId, optionIndex) {
    const poll = pollsData.find(p => p.id === pollId);
    if (!poll || poll.hasVoted) return;
    
    // Add vote
    poll.options[optionIndex].votes++;
    poll.hasVoted = true;
    poll.showResults = true;
    
    // Re-render polls to show results
    renderPolls();
    
    // Show success message
    showNotification('Vote recorded! Thanks for participating.');
}

// Render poll chart using Chart.js
function renderPollChart(poll) {
    const canvas = document.getElementById(`pollChart-${poll.id}`);
    if (!canvas) return;
    
    // Destroy existing chart if it exists
    if (chartInstances[poll.id]) {
        chartInstances[poll.id].destroy();
    }
    
    const ctx = canvas.getContext('2d');
    const labels = poll.options.map(opt => opt.text);
    const data = poll.options.map(opt => opt.votes);
    
    // Generate colors
    const colors = [
        'rgba(129, 140, 248, 0.8)',
        'rgba(167, 139, 250, 0.8)',
        'rgba(99, 102, 241, 0.8)',
        'rgba(245, 158, 11, 0.8)',
        'rgba(16, 185, 129, 0.8)',
        'rgba(6, 182, 212, 0.8)'
    ];
    
    const borderColors = [
        'rgba(129, 140, 248, 1)',
        'rgba(167, 139, 250, 1)',
        'rgba(99, 102, 241, 1)',
        'rgba(245, 158, 11, 1)',
        'rgba(16, 185, 129, 1)',
        'rgba(6, 182, 212, 1)'
    ];
    
    chartInstances[poll.id] = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors.slice(0, poll.options.length),
                borderColor: borderColors.slice(0, poll.options.length),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        padding: 10,
                        font: {
                            size: 11
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} votes (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

// Setup poll creation functionality
function setupPollCreation() {
    const addOptionBtn = document.getElementById('add-poll-option');
    const submitBtn = document.querySelector('#polls .bg-indigo-600.text-white.py-2');
    const questionInput = document.querySelector('input[placeholder="Poll question..."]');
    const optionsContainer = document.getElementById('poll-options-container');
    
    if (!addOptionBtn || !submitBtn || !questionInput || !optionsContainer) return;
    
    let optionCount = 2;
    
    // Add option button
    addOptionBtn.addEventListener('click', () => {
        if (optionCount >= 6) {
            showNotification('Maximum 6 options allowed per poll');
            return;
        }
        
        optionCount++;
        const newOption = document.createElement('input');
        newOption.type = 'text';
        newOption.placeholder = `Option ${optionCount}`;
        newOption.className = 'w-full p-2 border border-gray-300 rounded-md';
        optionsContainer.appendChild(newOption);
    });
    
    // Submit poll button
    submitBtn.addEventListener('click', () => {
        const question = questionInput.value.trim();
        const optionInputs = optionsContainer.querySelectorAll('input');
        const options = [];
        
        // Validate question
        if (!question) {
            showNotification('Please enter a poll question');
            return;
        }
        
        // Collect and validate options
        optionInputs.forEach(input => {
            const optionText = input.value.trim();
            if (optionText) {
                options.push({
                    text: optionText,
                    votes: 0
                });
            }
        });
        
        if (options.length < 2) {
            showNotification('Please provide at least 2 options');
            return;
        }
        
        // Create new poll
        const newPoll = {
            id: Date.now(),
            question: question,
            options: options,
            hasVoted: false,
            showResults: false
        };
        
        // Add to beginning of polls array
        pollsData.unshift(newPoll);
        
        // Re-render polls
        renderPolls();
        
        // Clear form
        questionInput.value = '';
        optionsContainer.innerHTML = `
            <input type="text" placeholder="Option 1" class="w-full p-2 border border-gray-300 rounded-md">
            <input type="text" placeholder="Option 2" class="w-full p-2 border border-gray-300 rounded-md">
        `;
        optionCount = 2;
        
        // Scroll to top to show new poll
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        showNotification('Poll created successfully!');
    });
}

// ============================================
// MEMES FUNCTIONALITY
// ============================================

// Memes data stored in memory
let memesData = [
    {
        id: 1,
        image: 'https://placehold.co/600x400/CCCCCC/FFFFFF?text=Funny+Meme',
        caption: 'When you finally understand a lecture.',
        likes: 15,
        liked: false,
        comments: [
            { username: 'jane_doe', text: 'So true!' },
            { username: 'john_smith', text: 'LMAO literally me last week.' }
        ]
    },
    {
        id: 2,
        image: 'https://placehold.co/600x400/CCCCCC/FFFFFF?text=Another+Meme',
        caption: 'Finals week got me like...',
        likes: 22,
        liked: false,
        comments: []
    }
];

// Render all memes
function renderMemes() {
    const memesContainer = document.querySelector('.grid');
    if (!memesContainer) return;
    
    memesContainer.innerHTML = '';
    
    memesData.forEach(meme => {
        const memeCard = createMemeCard(meme);
        memesContainer.appendChild(memeCard);
    });
}

// Create a meme card element
function createMemeCard(meme) {
    const card = document.createElement('div');
    card.className = 'rounded-lg overflow-hidden shadow-md border border-gray-200 flex flex-col';
    card.dataset.memeId = meme.id;
    
    card.innerHTML = `
        <img src="${meme.image}" alt="Campus Meme" class="w-full h-48 object-cover">
        <div class="p-4 flex-grow">
            <p class="text-sm">${meme.caption}</p>
            <div class="flex justify-between items-center mt-2 text-gray-500">
                <div>
                    <button class="like-btn hover:text-indigo-600 ${meme.liked ? 'text-indigo-600' : ''}" title="Like">
                        <i class="fas fa-thumbs-up"></i> <span class="like-count">${meme.likes}</span>
                    </button>
                    <button class="ml-3 hover:text-indigo-600" title="Comment">
                        <i class="fas fa-comment"></i> <span class="comment-count">${meme.comments.length}</span>
                    </button>
                </div>
                <button class="hover:text-indigo-600" title="Share"><i class="fas fa-share"></i></button>
            </div>
        </div>
        <div class="p-4 border-t bg-gray-50">
            <h4 class="text-sm font-semibold mb-2">Comments</h4>
            <div class="comments-list space-y-2 text-xs mb-3">
                ${meme.comments.map(comment => `
                    <p><strong class="text-indigo-700">@${comment.username}:</strong> ${comment.text}</p>
                `).join('')}
            </div>
            <input type="text" placeholder="Add a comment..." class="comment-input w-full p-2 border border-gray-300 rounded-md text-xs">
        </div>
    `;
    
    // Add event listeners
    const likeBtn = card.querySelector('.like-btn');
    likeBtn.addEventListener('click', () => handleLike(meme.id));
    
    const commentInput = card.querySelector('.comment-input');
    commentInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && commentInput.value.trim()) {
            handleComment(meme.id, commentInput.value.trim());
            commentInput.value = '';
        }
    });
    
    return card;
}

// Handle like button click
function handleLike(memeId) {
    const meme = memesData.find(m => m.id === memeId);
    if (!meme) return;
    
    if (meme.liked) {
        meme.likes--;
        meme.liked = false;
    } else {
        meme.likes++;
        meme.liked = true;
    }
    
    // Update the UI for this specific meme
    const card = document.querySelector(`[data-meme-id="${memeId}"]`);
    const likeBtn = card.querySelector('.like-btn');
    const likeCount = card.querySelector('.like-count');
    
    likeCount.textContent = meme.likes;
    if (meme.liked) {
        likeBtn.classList.add('text-indigo-600');
    } else {
        likeBtn.classList.remove('text-indigo-600');
    }
}

// Handle adding a comment
function handleComment(memeId, commentText) {
    const meme = memesData.find(m => m.id === memeId);
    if (!meme) return;
    
    // Add new comment with a username (in a real app, this would be the logged-in user)
    const newComment = {
        username: 'current_user',
        text: commentText
    };
    
    meme.comments.push(newComment);
    
    // Update the UI for this specific meme
    const card = document.querySelector(`[data-meme-id="${memeId}"]`);
    const commentsList = card.querySelector('.comments-list');
    const commentCount = card.querySelector('.comment-count');
    
    // Add new comment to the DOM
    const commentElement = document.createElement('p');
    commentElement.innerHTML = `<strong class="text-indigo-700">@${newComment.username}:</strong> ${newComment.text}`;
    commentsList.appendChild(commentElement);
    
    // Update comment count
    commentCount.textContent = meme.comments.length;
    
    // Add a subtle animation
    commentElement.style.opacity = '0';
    setTimeout(() => {
        commentElement.style.transition = 'opacity 0.3s';
        commentElement.style.opacity = '1';
    }, 10);
}

// Setup meme upload functionality
function setupMemeUpload() {
    const uploadBtn = document.querySelector('#memes .bg-indigo-600.text-white.py-2');
    const fileInput = document.getElementById('meme-upload');
    const captionInput = document.querySelector('input[placeholder="Add a witty caption..."]');
    
    if (!uploadBtn || !fileInput || !captionInput) return;
    
    uploadBtn.addEventListener('click', () => {
        const file = fileInput.files[0];
        const caption = captionInput.value.trim();
        
        if (!caption) {
            showNotification('Please add a caption for your meme!');
            return;
        }
        
        let imageUrl = 'https://placehold.co/600x400/CCCCCC/FFFFFF?text=New+Meme';
        
        // If a file was selected, create a URL for it
        if (file) {
            imageUrl = URL.createObjectURL(file);
        }
        
        // Create new meme object
        const newMeme = {
            id: Date.now(),
            image: imageUrl,
            caption: caption,
            likes: 0,
            liked: false,
            comments: []
        };
        
        // Add to beginning of array
        memesData.unshift(newMeme);
        
        // Re-render memes
        renderMemes();
        
        // Clear inputs
        fileInput.value = '';
        captionInput.value = '';
        
        // Scroll to top to show new meme
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        showNotification('Meme uploaded successfully!');
    });
}


// ============================================
// SHARED UTILITIES
// ============================================

// Show notification message
function showNotification(message) {
    // Remove existing notification if any
    const existing = document.querySelector('.notification');
    if (existing) existing.remove();
    
    const notification = document.createElement('div');
    notification.className = 'notification fixed top-20 right-4 bg-indigo-600 text-white px-6 py-3 rounded-lg shadow-lg z-50';
    notification.textContent = message;
    notification.style.transition = 'opacity 0.3s';
    
    document.body.appendChild(notification);
    
    // Fade out and remove after 3 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Polls page
    if (document.querySelector('#polls')) {
        renderPolls();
        setupPollCreation();
    }
    
    // Initialize Memes page
    if (document.querySelector('#memes')) {
        renderMemes();
        setupMemeUpload();
    }
    
    // Login Page: Forgot Password
    const forgotPasswordLink = document.getElementById('forgot-password');
    if (forgotPasswordLink) {
        forgotPasswordLink.addEventListener('click', function(e) {
            e.preventDefault();
            showNotification("A password reset link has been sent to your email address.");
        });
    }

    // Registration Page: Handle Sign Up
    const registerForm = document.querySelector('form[action="login.html"]');
    if (registerForm && document.getElementById('confirm-password')) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;

            if (!username || !email || !password || !confirmPassword) {
                showNotification('Please fill in all fields.');
                return;
            }

            if (password !== confirmPassword) {
                showNotification('Passwords do not match.');
                return;
            }

            // Simulate successful registration
            showNotification(`Welcome, ${username}! Your account has been created successfully.`);
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
        });
    }
});