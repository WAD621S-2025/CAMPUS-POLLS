<form action="add_event.php" method="POST">
    <div>
        <label for="event-title">Event Title</label>
        <input type="text" name="title" id="event-title" required>
    </div>

    <div>
        <label for="event-category">Event Category</label>
        <select name="category" id="event-category" required>
            <option value="">Select Category</option>
            <option value="academic">Academic</option>
            <option value="social">Social</option>
            <option value="sports">Sports</option>
            <option value="workshop">Workshop</option>
        </select>
    </div>

    <div>
        <label for="event-description">Event Description</label>
        <textarea name="description" id="event-description" required></textarea>
    </div>

    <div>
        <label for="event-date">Event Date & Time</label>
        <input type="datetime-local" name="event_date" id="event-date" required>
    </div>

    <button type="submit">Create Event</button>
</form>
