
$(document).ready(function() {


  // Polls Features
  function fetchPolls() {
    $.ajax({
      url: 'get_polls.php',
      method: 'GET',
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          pollsData = response.polls;
          renderPolls();
        } else {
          showNotification('Failed to load polls.');
        }
      },
      error: function() {
        showNotification('Network error loading polls.');
      }
    });
  }

  function renderPolls() {
    const $pollsContainer = $('.space-y-6');
    if (!$pollsContainer.length) return;
    $pollsContainer.empty();

    pollsData.forEach(poll => {
      const $card = $('<div>').addClass('border border-gray-200 p-4 rounded-lg').attr('data-poll-id', poll.id);

      if (poll.showResults || poll.hasVoted) {
        // Show chart and results
        const totalVotes = poll.options.reduce((sum, opt) => sum + opt.votes, 0);
        let optionsHTML = '';
        poll.options.forEach(opt => {
          const percentage = totalVotes > 0 ? ((opt.votes / totalVotes) * 100).toFixed(1) : 0;
          optionsHTML += `<div class="flex justify-between items-center">
                            <span>${opt.text}</span>
                            <span class="text-gray-600">${opt.votes} votes (${percentage}%)</span>
                          </div>`;
        });

        $card.html(`
          <p class="font-semibold mb-4">${poll.question}</p>
          <div class="flex justify-center items-center mb-4">
            <div class="w-full max-w-xs">
              <canvas id="pollChart-${poll.id}"></canvas>
            </div>
          </div>
          <div class="space-y-2 text-sm">${optionsHTML}</div>
        `);

        $pollsContainer.append($card);
        renderPollChart(poll);
      } else {
        // Voting buttons view
        let optionsHTML = '';
        poll.options.forEach((opt, index) => {
          optionsHTML += `<button class="vote-btn w-full text-left p-2 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors" data-option-index="${index}">
                            ${opt.text}
                          </button>`;
        });

        $card.html(`
          <p class="font-semibold">${poll.question}</p>
          <div class="mt-2 space-y-2 poll-options">${optionsHTML}</div>
          <button class="view-results-btn mt-3 text-sm text-indigo-600 hover:underline">View Results</button>
        `);

        $pollsContainer.append($card);

        // Vote button event
        $card.find('.vote-btn').click(function() {
          const optionIndex = $(this).data('option-index');
          handleVote(poll.id, optionIndex);
        });

        // View results button event
        $card.find('.view-results-btn').click(function() {
          poll.showResults = true;
          renderPolls();
        });
      }
    });
  }

  function handleVote(pollId, optionIndex) {
    const poll = pollsData.find(p => p.id === pollId);
    if (!poll || poll.hasVoted) return;

    const option = poll.options[optionIndex];

    $.ajax({
      url: 'vote.php',
      method: 'POST',
      dataType: 'json',
      data: { poll_id: pollId, option_id: option.id },
      success: function(response) {
        if (response.success) {
          // Update local data from server response if available
          poll.hasVoted = true;
          poll.showResults = true;

          if (response.updatedOptions) {
            poll.options = response.updatedOptions;
          } else {
            option.votes++;
          }

          renderPolls();
          showNotification('Vote recorded! Thanks for participating.');
        } else {
          showNotification(response.message || 'Error recording vote.');
        }
      },
      error: function() {
        showNotification('Network error recording vote.');
      }
    });
  }

  function renderPollChart(poll) {
    const ctx = document.getElementById(`pollChart-${poll.id}`);
    if (!ctx) return;

    if (chartInstances[poll.id]) {
      chartInstances[poll.id].destroy();
    }
  
    const labels = poll.options.map(opt => opt.text);
    const data = poll.options.map(opt => opt.votes);

    const colors = [
      'rgba(129, 140, 248, 0.8)',
      'rgba(167, 139, 250, 0.8)',
      'rgba(99, 102, 241, 0.8)',
      'rgba(245, 158, 11, 0.8)',
      'rgba(16, 185, 129, 0.8)',
      'rgba(6, 182, 212, 0.8)'
    ];

    const borderColors = colors.map(c => c.replace('0.8', '1'));

    chartInstances[poll.id] = new Chart(ctx.getContext('2d'), {
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
          legend: { position: 'top' },
          tooltip: {
            callbacks: {
              label: ctx => {
                const label = ctx.label || '';
                const value = ctx.parsed || 0;
                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                const percent = total ? ((value / total) * 100).toFixed(1) : 0;
                return `${label}: ${value} votes (${percent}%)`;
              }
            }
          }
        }
      }
    });
  }

  function fetchMemes() {
    $.ajax({
      url: 'get_memes.php',
      method: 'GET',
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          memesData = response.memes;
          renderMemes();
        } else {
          showNotification('Failed to load memes.');
        }
      },
      error: function() {
        showNotification('Network error loading memes.');
      }
    });
  }

  function renderMemes() {
    const $memesContainer = $('.grid');
    if (!$memesContainer.length) return;
    
    $memesContainer.empty();
  
    memesData.forEach(meme => {
      const $card = $(`
        <div class="rounded-lg overflow-hidden shadow-md border border-gray-200 flex flex-col" data-meme-id="${meme.id}">
          <img src="${meme.image}" alt="Campus Meme" class="w-full h-48 object-cover" />
          <div class="p-4 flex-grow">
            <p class="text-sm">${meme.caption}</p>
            <div class="flex justify-between items-center mt-2 text-gray-500">
              <div>
                <button class="like-btn ${meme.liked ? 'text-indigo-600' : ''}" title="Like">
                  <i class="fas fa-thumbs-up"></i> <span class="like-count">${meme.likes}</span>
                </button>
                <button class="ml-3" title="Comment">
                  <i class="fas fa-comment"></i> <span class="comment-count">${meme.comments.length}</span>
                </button>
              </div>
              <button title="Share"><i class="fas fa-share"></i></button>
            </div>
          </div>
          <div class="p-4 border-t bg-gray-50">
            <h4 class="text-sm font-semibold mb-2">Comments</h4>
            <div class="comments-list space-y-2 text-xs mb-3"></div>
            <input type="text" placeholder="Add a comment..." class="comment-input w-full p-2 border border-gray-300 rounded-md text-xs" />
          </div>
        </div>
      `);

      // Populate comments
      meme.comments.forEach(c => {
        $card.find('.comments-list').append(
          `<p><strong class="text-indigo-700">@${c.username}:</strong> ${c.text}</p>`
        );
      });

      // Add event listeners
      $card.find('.like-btn').click(() => handleLike(meme.id));
      $card.find('.comment-input').keypress(e => {
        if (e.which === 13 && e.target.value.trim()) {
          handleComment(meme.id, e.target.value.trim());
          e.target.value = '';
        }
      });

      $memesContainer.append($card);
    });
  }

  function handleLike(memeId) {
    $.ajax({
      url: 'meme_like.php',
      method: 'POST',
      dataType: 'json',
      data: { meme_id: memeId },
      success: function(res) {
        if (res.success) {
          const meme = memesData.find(m => m.id === memeId);
          meme.liked = !meme.liked;
          meme.likes = res.likes_count;
          renderMemes();
        } else {
          showNotification('Could not update like.');
        }
      },
      error: function() {
        showNotification('Network error updating like.');
      }
    });
  }

  function handleComment(memeId, commentText) {
    $.ajax({
      url: 'meme_comment.php',
      method: 'POST',
      dataType: 'json',
      data: { meme_id: memeId, comment_text: commentText },
      success: function(res) {
        if (res.success) {
          const meme = memesData.find(m => m.id === memeId);
          meme.comments.push({ username: res.username, text: commentText });
          renderMemes();
        } else {
          showNotification('Could not add comment.');
        }
      },
      error: function() {
        showNotification('Network error adding comment.');
      }
    });
  }

  function showNotification(message) {
    const $notify = $('<div class="notification fixed top-20 right-4 bg-indigo-600 text-white px-6 py-3 rounded-lg shadow-lg z-50"></div>');
    $notify.text(message);
    $('body').append($notify);
    $notify.fadeOut(3000, () => $notify.remove());
  }


  // Fetch data on page load
  fetchPolls();
  fetchMemes();
});
