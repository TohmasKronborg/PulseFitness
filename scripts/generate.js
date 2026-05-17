let i = 0;
const max = 4;

const nextBtn = document.querySelector("#btnNext");
const nextBtnDiv = document.querySelector("#btnNextDiv");
const nextBtn2 = document.querySelector("#btnNext2");

const backBtn = document.querySelector("#btnBack");
const backBtnDiv = document.querySelector("#btnBackDiv");

const btnFirstHome = document.querySelector("#btnFirstHome");
const btnHome = document.querySelector("#btnHome");

const steps = document.querySelectorAll(".step");
const indicators = document.querySelectorAll(".step-indicator");

function updateUI() {

    // STEP VISIBILITY
    steps.forEach((step, idx) => {
        step.style.display = idx === i ? "block" : "none";
    });

    // INDICATOR STATE
    indicators.forEach((indicator, idx) => {

        if (idx <= i) {
            indicator.classList.add("bg-primary", "text-light");
            indicator.classList.remove("bg-white", "text-dark");
        } else {
            indicator.classList.remove("bg-primary", "text-light");
            indicator.classList.add("bg-white", "text-dark");
        }

    });

    // BACK BUTTON STATE
    if (i === 0) {
        backBtnDiv.classList.add("d-none");
        btnFirstHome.classList.remove("d-none");
        btnHome.classList.add("d-none");
    } else {
        backBtnDiv.classList.remove("d-none");
        btnFirstHome.classList.add("d-none");
        btnHome.classList.remove("d-none");
    }

    // NEXT BUTTON STATE
    if (i === max) {
        nextBtnDiv.classList.add("d-none");
        nextBtn2.classList.remove("d-none");
    } else {
        nextBtnDiv.classList.remove("d-none");
        nextBtn2.classList.add("d-none");
    }

    console.log(i);
}

// NEXT
function next() {
    if (i < max) {
        i++;
        updateUI();
    }
}

// BACK
function back() {
    if (i > 0) {
        i--;
        updateUI();
    }
}

// BUTTON EVENTS
if (nextBtn) {
    nextBtn.addEventListener("click", next);
}

if (backBtn) {
    backBtn.addEventListener("click", back);
}

// INDICATOR EVENTS
indicators.forEach((indicator, idx) => {
    indicator.addEventListener("click", () => {
        i = idx;
        updateUI();
    });
});

// INITIAL RENDER
updateUI();