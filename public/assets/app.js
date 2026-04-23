(() => {
    const board = document.getElementById("duel-board");
    if (!board) {
        return;
    }

    const leftCard = document.getElementById("left-card");
    const rightCard = document.getElementById("right-card");
    const leftName = document.getElementById("left-name");
    const rightName = document.getElementById("right-name");
    const leftSprite = document.getElementById("left-sprite");
    const rightSprite = document.getElementById("right-sprite");
    const leftBg = document.getElementById("left-bg");
    const rightBg = document.getElementById("right-bg");
    const leftElo = document.getElementById("left-elo");
    const rightElo = document.getElementById("right-elo");
    const leftEloReveal = document.getElementById("left-elo-reveal");
    const rightEloReveal = document.getElementById("right-elo-reveal");
    const feedback = document.getElementById("duel-feedback");

    const categoryId = Number(board.dataset.categoryId || 0);
    const csrf = board.dataset.csrf || "";

    let currentPair = null;
    let loading = false;

    const uniqueUrls = (values) => {
        const seen = new Set();

        return values.filter((value) => {
            if (!value || typeof value !== "string") {
                return false;
            }

            const normalized = value.trim();
            if (!normalized || seen.has(normalized)) {
                return false;
            }

            seen.add(normalized);
            return true;
        });
    };

    const getHomeModelUrl = (pokedexId) => {
        if (!Number.isInteger(pokedexId) || pokedexId <= 0) {
            return "";
        }

        return `https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/home/${pokedexId}.png`;
    };

    const setPokemonImage = (imgElement, bgElement, pokemon) => {
        const pokedexId = Number(pokemon.pokedex_id || 0);
        const sources = uniqueUrls([
            pokemon.official_artwork_url,
            pokemon.sprite_url,
            getHomeModelUrl(pokedexId),
        ]);

        if (sources.length === 0) {
            imgElement.src = "";
            bgElement.style.backgroundImage = "none";
            return;
        }

        let index = 0;

        imgElement.onerror = () => {
            index += 1;
            if (index >= sources.length) {
                imgElement.onerror = null;
                imgElement.src = "";
                bgElement.style.backgroundImage = "none";
                return;
            }

            const src = sources[index];
            imgElement.src = src;
            bgElement.style.backgroundImage = `url("${src}")`;
        };

        const src = sources[index];
        imgElement.src = src;
        bgElement.style.backgroundImage = `url("${src}")`;
    };

    const setLoadingState = (isLoading) => {
        loading = isLoading;
        if (isLoading) {
            leftCard.style.pointerEvents = "none";
            rightCard.style.pointerEvents = "none";
        } else {
            leftCard.style.pointerEvents = "auto";
            rightCard.style.pointerEvents = "auto";
        }
    };

    const setFeedback = (message, level = "info") => {
        feedback.textContent = message;
        feedback.dataset.level = level;
        if (!message) {
            feedback.style.opacity = "0";
        } else {
            feedback.style.opacity = "1";
        }
    };

    const animateValue = (obj, start, end, duration) => {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            obj.innerHTML = Math.floor(progress * (end - start) + start);
            if (progress < 1) {
                window.requestAnimationFrame(step);
            } else {
                obj.innerHTML = end.toFixed(2);
            }
        };
        window.requestAnimationFrame(step);
    };

    const resetCards = () => {
        leftCard.classList.remove("correct", "incorrect");
        rightCard.classList.remove("correct", "incorrect");
        leftEloReveal.classList.remove("visible");
        rightEloReveal.classList.remove("visible");
        leftElo.textContent = "0";
        rightElo.textContent = "0";
    };

    const loadPair = async () => {
        setLoadingState(true);
        setFeedback("");
        resetCards();

        try {
            const response = await fetch(`/api/next-pair.php?category_id=${categoryId}`, {
                credentials: "same-origin",
                headers: {
                    Accept: "application/json",
                },
            });

            const payload = await response.json();

            if (!response.ok || !payload.success) {
                currentPair = null;
                leftName.textContent = "No match";
                rightName.textContent = "No match";
                leftSprite.src = "";
                rightSprite.src = "";
                setFeedback(payload.error || "Could not load a pair.", "error");
                return;
            }

            currentPair = payload.pair;
            leftName.textContent = payload.pair.left.name;
            rightName.textContent = payload.pair.right.name;
            setPokemonImage(leftSprite, leftBg, payload.pair.left);
            setPokemonImage(rightSprite, rightBg, payload.pair.right);
            leftSprite.alt = payload.pair.left.name;
            rightSprite.alt = payload.pair.right.name;
            setFeedback("");
        } catch (error) {
            currentPair = null;
            setFeedback("Network error while loading a pair.", "error");
        } finally {
            setLoadingState(false);
        }
    };

    const submitVote = async (winnerId, clickedSide) => {
        if (!currentPair || loading) {
            return;
        }

        setLoadingState(true);
        setFeedback("");

        try {
            const payload = {
                csrf,
                category_id: categoryId,
                left_id: currentPair.left.id,
                right_id: currentPair.right.id,
                winner_id: winnerId,
            };

            const response = await fetch("/api/vote.php", {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify(payload),
            });

            const body = await response.json();

            if (!response.ok || !body.success) {
                setFeedback(body.error || "Vote failed.", "error");
                setTimeout(loadPair, 1500);
                return;
            }

            const result = body.result;
            const categoryRatings = result.category_ratings;
            
            const isLeftWinner = result.winner_id === currentPair.left.id;
            
            if (clickedSide === "left") {
                leftCard.classList.add("correct");
                rightCard.classList.add("incorrect");
            } else {
                rightCard.classList.add("correct");
                leftCard.classList.add("incorrect");
            }

            leftEloReveal.classList.add("visible");
            rightEloReveal.classList.add("visible");
            
            const startScore = 1500;
            const leftTarget = isLeftWinner ? categoryRatings.winner : categoryRatings.loser;
            const rightTarget = isLeftWinner ? categoryRatings.loser : categoryRatings.winner;
            
            animateValue(leftElo, startScore, leftTarget, 1000);
            animateValue(rightElo, startScore, rightTarget, 1000);

            setTimeout(loadPair, 2000);
        } catch (error) {
            setFeedback("Network error while submitting vote.", "error");
            setTimeout(loadPair, 1500);
        }
    };

    leftCard.addEventListener("click", () => {
        if (!currentPair || loading) return;
        submitVote(currentPair.left.id, "left");
    });
    
    leftCard.addEventListener("keydown", (e) => {
        if (e.key === "Enter" || e.key === " ") {
            if (!currentPair || loading) return;
            submitVote(currentPair.left.id, "left");
        }
    });

    rightCard.addEventListener("click", () => {
        if (!currentPair || loading) return;
        submitVote(currentPair.right.id, "right");
    });
    
    rightCard.addEventListener("keydown", (e) => {
        if (e.key === "Enter" || e.key === " ") {
            if (!currentPair || loading) return;
            submitVote(currentPair.right.id, "right");
        }
    });

    loadPair();
})();
