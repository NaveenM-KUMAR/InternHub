const roles = [
    { code: 'PA', title: 'Programmer Analyst', skills: 'Python, SQL, AWS', experience: '3 Years', qCount: 20 },
    { code: 'AE', title: 'AI Engineer', skills: 'Python, R, Jupyter, Docker', experience: '1 Year', qCount: 10 },
    { code: 'FS', title: 'Full Stack Developer', skills: 'MERN Stack', experience: '3 Years', qCount: 10 },
    { code: 'DA', title: 'Database Administrator', skills: 'SQL, Database Schema, PostgreSQL, NoSQL', experience: '1 Year', qCount: 20 },
    { code: 'DS', title: 'Data Scientist', skills: 'Python, R, Tensorflow', experience: '1 Year', qCount: 10 },
    { code: 'BD', title: 'Backend Developer', skills: 'MongoDB, NodeJS, ExpressJS, SQL', experience: '3 Years', qCount: 10 },
    { code: 'FD', title: 'Frontend Developer', skills: 'CSS, HTML, JavaScript, React', experience: '1 Year', qCount: 20 },
];

// Generate cards
const roleGrid = document.getElementById('roleGrid');
roles.forEach(role => {
    const card = document.createElement('div');
    card.classList.add('role-card');
    card.innerHTML = `
        <div class="role-code">${role.code}</div>
        <h3>${role.title}</h3>
        <p>${role.skills}</p>
        <p>Experience: ${role.experience}</p>
        <p>${role.qCount} Q&A</p>
    `;
    card.addEventListener('click', () => openQuestions(role));
    roleGrid.appendChild(card);
});

// Modal functionality
const modal = document.getElementById('questionModal');
const closeModal = document.querySelector('.close');
const roleTitle = document.getElementById('roleTitle');
const questionsContainer = document.getElementById('questionsContainer');

function openQuestions(role) {
    roleTitle.innerText = role.title + " Questions";
    questionsContainer.innerHTML = "<p>Generating questions...</p>";
    modal.style.display = 'block';

    // Simulate fetching questions (replace with API call to AI backend)
    setTimeout(() => {
        questionsContainer.innerHTML = "";
        for(let i = 1; i <= role.qCount; i++){
            const q = document.createElement('p');
            q.innerText = `${i}. Sample question for ${role.title}`;
            questionsContainer.appendChild(q);
        }
    }, 1000);
}

closeModal.onclick = () => modal.style.display = "none";
window.onclick = e => { if(e.target == modal) modal.style.display = "none"; }
