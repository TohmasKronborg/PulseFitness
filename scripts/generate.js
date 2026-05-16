let i = 0; // current step index
const max = 4;

const nextBtn = document.querySelector("#btnNext");
const nextBtnDiv = document.querySelector("#btnNextDiv");
const nextBtn2 = document.querySelector("#btnNext2");

const backBtn = document.querySelector("#btnBack");
const backBtnDiv = document.querySelector("#btnBackDiv");

const btnFirstHome = document.querySelector("#btnFirstHome");
const btnHome = document.querySelector("#btnHome");

const steps = document.querySelectorAll(".step"); // your pages
const indicators = document.querySelectorAll(".step-indicator");

function updateUI() {
    // STEP VISIBILITY
    steps.forEach((step, idx) => {
        step.style.display = idx === i ? "block" : "none";
    });

    // INDICATOR STATE
    indicators.forEach((indicator, idx) => {
        if (idx === i) {
            indicator.classList.add("bg-primary", "text-light");
            indicator.classList.remove("bg-white", "text-dark");
        } else {
            indicator.classList.remove("bg-primary", "text-light");
            indicator.classList.add("bg-white", "text-dark");
        }
    });
}

// NEXT
function next() {
    if (i < max) {
        i++;
        updateUI();
        console.log(i);
    }

    if (i > 0) {
        backBtnDiv.classList.remove("d-none")
        btnFirstHome.classList.add("d-none")
        btnHome.classList.remove("d-none")
    }

    if (i === max) {
        nextBtnDiv.classList.add("d-none")
        nextBtn2.classList.remove("d-none")
    }
}

// BACK
function back() {
    if (i > 0) {
        i--;
        updateUI();
        console.log(i);
    }

    if (i === 0) {
        backBtnDiv.classList.add("d-none")
        btnFirstHome.classList.remove("d-none")
        btnHome.classList.add("d-none")
    }

    if (i < max) {
        nextBtnDiv.classList.remove("d-none")
        nextBtn2.classList.add("d-none")
    }
}

// event listeners
if (nextBtn) nextBtn.addEventListener("click", next);
if (backBtn) backBtn.addEventListener("click", back);

indicators.forEach((indicator, idx) => {
    indicator.addEventListener("click", () => {
        i = idx;
        updateUI();
    });
});

// initial render
updateUI();