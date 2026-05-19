document.addEventListener("DOMContentLoaded", () => {
    let i = 0;

    const steps = document.querySelectorAll(".step");
    const indicators = document.querySelectorAll(".step-indicator");

    const max = steps.length - 1;

    const nextBtn = document.querySelector("#btnNext");
    const backBtn = document.querySelector("#btnBack");

    const nextBtnDiv = document.querySelector("#btnNextDiv");
    const nextBtnSubmit = document.querySelector("#btnSubmit");

    const backBtnDiv = document.querySelector("#btnBackDiv");
    const btnFirstHome = document.querySelector("#btnFirstHome");
    const btnHome = document.querySelector("#btnHome");

    function updateUI() {

        // STEP VISIBILITY
        steps.forEach((step, idx) => {
            step.style.display = idx === i ? "block" : "none";
        });

        // INDICATORS
        indicators.forEach((indicator, idx) => {
            if (idx <= i) {
                indicator.classList.add("bg-primary", "text-light");
                indicator.classList.remove("bg-white", "text-dark");
            } else {
                indicator.classList.remove("bg-primary", "text-light");
                indicator.classList.add("bg-white", "text-dark");
            }
        });

        // BACK / HOME UI (SAFE)
        if (i === 0) {
            backBtnDiv?.classList.add("d-none");
            btnFirstHome?.classList.remove("d-none");
            btnHome?.classList.add("d-none");
        } else {
            backBtnDiv?.classList.remove("d-none");
            btnFirstHome?.classList.add("d-none");
            btnHome?.classList.remove("d-none");
        }

        // NEXT / SUBMIT UI (SAFE)
        if (i === max) {
            nextBtnDiv?.classList.add("d-none");
            nextBtnSubmit?.classList.remove("d-none");
        } else {
            nextBtnDiv?.classList.remove("d-none");
            nextBtnSubmit?.classList.add("d-none");
        }
    }

    function next() {
        if (i < max) {
            i++;
            updateUI();
        }
    }

    function back() {
        if (i > 0) {
            i--;
            updateUI();
        }
    }

    // EVENTS (SAFE BINDING)
    nextBtn?.addEventListener("click", next);
    backBtn?.addEventListener("click", back);

    indicators.forEach((indicator, idx) => {
        indicator.addEventListener("click", () => {
            i = idx;
            updateUI();
        });
    });

    updateUI();
});