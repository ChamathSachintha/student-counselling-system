// Escape stored text before including it in appointment table markup.
function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[character]));
}

// Send requests and surface API failures to the page controls.
async function api(url, data) {
    const response = await fetch(url, data ? {method: 'POST', body: new URLSearchParams(data)} : {});
    const result = await response.json();
    if (!response.ok || !result.success) throw new Error(result.message || 'Request failed.');
    return result;
}

// Render a safe status label using the supported appointment states.
function statusLabel(status) {
    const states = ['Pending', 'Approved', 'Rejected', 'Completed', 'Cancelled'];
    const className = states.includes(status) ? 'status-' + status.toLowerCase() : '';
    return `<span class="status ${className}">${escapeHtml(status)}</span>`;
}

// Display the student's appointments and available cancellation controls.
async function loadAppointments() {
    const body = document.getElementById('appointment-table-body');
    if (!body) return;
    try {
        const data = await api('php/appointment.php');
        document.getElementById('appointment-count').textContent = data.appointments.length;
        body.innerHTML = data.appointments.map(a => `<tr>
            <td>${escapeHtml(a.counselor_name)}</td><td>${escapeHtml(a.appointment_date)}</td>
            <td>${escapeHtml(a.appointment_time)}</td><td>${escapeHtml(a.reason)}</td>
            <td>${statusLabel(a.status)}${['Pending','Approved'].includes(a.status) ?
                `<br><button class="btn btn-danger" onclick="cancelAppointment(${Number(a.appointment_id)})">Cancel</button>` : ''}</td>
            </tr>`).join('') || '<tr><td colspan="5">No appointments found.</td></tr>';
    } catch (error) { body.innerHTML = `<tr><td colspan="5">${escapeHtml(error.message)}</td></tr>`; }
}

// Cancel the selected appointment and refresh its current status.
async function cancelAppointment(appointmentId) {
    if (!confirm('Cancel this appointment?')) return;
    try { await api('php/cancel_appointment.php', {appointment_id: appointmentId}); await loadAppointments(); }
    catch (error) { alert(error.message); }
}

// Display counselor requests and actions for their current states.
async function loadCounselorAppointments() {
    const body = document.getElementById('counselor-appointment-body');
    if (!body) return;
    try {
        const data = await api('php/counselor_action.php');
        document.getElementById('request-count').textContent = data.appointments.length;
        document.getElementById('pending-count').textContent = data.appointments.filter(a => a.status === 'Pending').length;
        document.getElementById('approved-count').textContent = data.appointments.filter(a => a.status === 'Approved').length;
        body.innerHTML = data.appointments.map(a => {
            const actions = a.status === 'Pending' ? ['Approved','Rejected'] : a.status === 'Approved' ? ['Completed'] : [];
            const labels = {Approved:'Approve', Rejected:'Reject', Completed:'Mark completed'};
            return `<tr><td>${escapeHtml(a.student_name)}</td><td>${escapeHtml(a.appointment_date)}</td>
                <td>${escapeHtml(a.appointment_time)}</td><td>${escapeHtml(a.reason)}</td><td>${statusLabel(a.status)}</td>
                <td>${actions.map(status => `<button class="btn" onclick="updateAppointment(${Number(a.appointment_id)}, '${status}')">${labels[status]}</button>`).join(' ') || 'No action'}</td></tr>`;
        }).join('') || '<tr><td colspan="6">No appointment requests.</td></tr>';
    } catch (error) { body.innerHTML = `<tr><td colspan="6">${escapeHtml(error.message)}</td></tr>`; }
}

// Apply the counselor's decision and refresh the appointment list.
async function updateAppointment(appointmentId, status) {
    if (!confirm(`Set this appointment to ${status.toLowerCase()}?`)) return;
    try { await api('php/counselor_action.php', {appointment_id:appointmentId, status}); await loadCounselorAppointments(); }
    catch (error) { alert(error.message); }
}

// Populate booking, messaging, and feedback choices from current database accounts.
async function loadContacts() {
    const data = await api('php/contacts.php');
    for (const id of ['counselor', 'message-counselor', 'feedback-counselor']) {
        const select = document.getElementById(id);
        if (!select) continue;
        const previous = select.value;
        select.replaceChildren(new Option(data.contacts.length ? 'Select a person' : 'No contacts available', ''));
        data.contacts.forEach(contact => select.add(new Option(contact.name, String(contact.user_id))));
        if (data.contacts.some(contact => String(contact.user_id) === previous)) select.value = previous;
    }
    const count = document.getElementById('message-count');
    if (count) count.textContent = data.message_count;
}

// Send a message to the selected contact and refresh the conversation.
async function sendMessage() {
    const recipient = document.getElementById('message-counselor').value;
    const input = document.getElementById('message-text');
    if (!recipient || !input.value.trim()) return alert('Select a person and enter a message.');
    try {
        await api('php/message.php', {receiver_id:recipient, message:input.value.trim()});
        input.value = '';
        await loadMessages(recipient);
        await loadContacts();
    } catch (error) { alert(error.message); }
}

// Show message contents as text and identify who sent each message.
async function loadMessages(recipient) {
    const list = document.getElementById('message-list');
    if (!recipient) { list.textContent = 'Select a person to view messages.'; return; }
    try {
        const data = await api('php/message.php?receiver_id=' + encodeURIComponent(recipient));
        if (document.getElementById('message-counselor').value !== String(recipient)) return;
        list.replaceChildren();
        if (!data.messages.length) list.textContent = 'No messages yet.';
        data.messages.forEach(message => {
            const box = document.createElement('div');
            box.className = 'message-box';
            const content = document.createElement('p');
            content.textContent = message.message;
            const label = document.createElement('small');
            label.textContent = (Number(message.sender_id) === Number(data.user_id) ? 'You' : 'Contact') + ' · ' + message.sent_at;
            box.append(content, label);
            list.appendChild(box);
        });
    } catch (error) { list.textContent = error.message; }
}

// Display submitted feedback and update the dashboard total.
async function loadFeedback() {
    const list = document.getElementById('feedback-list');
    if (!list) return;
    try {
        const data = await api('php/feedback.php');
        document.getElementById('feedback-count').textContent = data.feedback.length;
        list.replaceChildren();
        if (!data.feedback.length) list.textContent = 'No feedback submitted yet.';
        data.feedback.forEach(item => {
            const entry = document.createElement('p');
            entry.textContent = `${item.counselor_name}: ${item.rating}/5: ${item.comment || ''}`;
            list.appendChild(entry);
        });
    } catch (error) { list.textContent = error.message; }
}

// Initialize dashboard data and connect messaging and feedback controls.
document.addEventListener('DOMContentLoaded', async () => {
    loadAppointments();
    loadCounselorAppointments();
    showLoginError();
    loadFeedback();
    const select = document.getElementById('message-counselor');
    if (select) {
        // Load the conversation when the selected contact changes.
        select.addEventListener('change', () => loadMessages(select.value));
        try { await loadContacts(); }
        catch (error) { document.getElementById('message-list').textContent = error.message; }
    }
    const refresh = document.getElementById('refresh-messages');
    // Reload contacts and messages when the refresh button is clicked.
    if (refresh) refresh.addEventListener('click', async () => {
        try { await loadContacts(); await loadMessages(select.value); } catch (error) { alert(error.message); }
    });
    const form = document.getElementById('feedback-form');
    // Submit feedback without navigation and refresh the displayed feedback.
    if (form) form.addEventListener('submit', async event => {
        event.preventDefault();
        try {
            const result = await api('php/feedback.php', Object.fromEntries(new FormData(form)));
            alert(result.message);
            form.reset();
            await loadFeedback();
        } catch (error) { alert(error.message); }
    });
});
// Show login error messages.
function showLoginError() {

    const box = document.getElementById("login-error");

    if (!box) return;


    const error =
        new URLSearchParams(
            window.location.search
        ).get("error");


    const messages = {

        email: "Incorrect email address.",

        password: "Incorrect password.",

        empty: "Please enter email and password.",

        request: "Invalid login request.",

        role: "Invalid account role."

    };


    if (messages[error]) {

        box.textContent = messages[error];

        box.className = "alert alert-error";

    }

}

