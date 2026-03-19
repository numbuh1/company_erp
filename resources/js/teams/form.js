document.addEventListener('DOMContentLoaded', () => {

    const unassigned = document.getElementById('unassigned-list');
    const assigned = document.getElementById('assigned-list');

    const searchUnassigned = document.getElementById('search-unassigned');
    const searchAssigned = document.getElementById('search-assigned');

    // -------------------------
    // MOVE BETWEEN LISTS
    // -------------------------
    document.addEventListener('change', (e) => {
        console.log('changed');

        if (e.target.classList.contains('assign-toggle')) {
            const item = e.target.closest('.user-item');
            const hiddenInput = item.querySelector('input[name="users[]"]');

            if (e.target.checked) {
                assigned.appendChild(item);
                hiddenInput.disabled = false;

                addLeaderCheckbox(item);
            } else {
                unassigned.appendChild(item);
                hiddenInput.disabled = true;

                removeLeaderCheckbox(item);
            }            

            sortLists();
        }

        if (e.target.classList.contains('leader-checkbox')) {
            const item = e.target.closest('.user-item');

            // ensure assigned
            item.querySelector('.assign-toggle').checked = true;

            sortLists();
        }
    });

    function addLeaderCheckbox(item) {
        if (!item.querySelector('.leader-checkbox')) {
            const wrapper = document.createElement('label');
            wrapper.className = "text-sm flex items-center space-x-1";

            wrapper.innerHTML = `
                <input type="checkbox" class="leader-checkbox" name="leaders[]" value="${item.dataset.id}">
                <span>Leader</span>
            `;

            item.appendChild(wrapper);
        }
    }

    function removeLeaderCheckbox(item) {
        const leader = item.querySelector('.leader-checkbox');
        if (leader) {
            leader.closest('label').remove();
        }
    }

    // -------------------------
    // SORTING
    // -------------------------
    function sortLists() {
        sortContainer(unassigned, false);
        sortContainer(assigned, true);
    }

    function sortContainer(container, leaderPriority) {
        const items = Array.from(container.children);

        items.sort((a, b) => {
            const nameA = a.innerText.toLowerCase();
            const nameB = b.innerText.toLowerCase();

            if (leaderPriority) {
                const aLeader = a.querySelector('.leader-checkbox')?.checked;
                const bLeader = b.querySelector('.leader-checkbox')?.checked;

                if (aLeader !== bLeader) {
                    return bLeader - aLeader;
                }
            }

            return nameA.localeCompare(nameB);
        });

        items.forEach(el => container.appendChild(el));
    }

    // -------------------------
    // SEARCH
    // -------------------------
    function setupSearch(input, container) {
        input.addEventListener('input', () => {
            const term = input.value.toLowerCase();

            Array.from(container.children).forEach(item => {
                const name = item.innerText.toLowerCase();
                item.style.display = name.includes(term) ? '' : 'none';
            });
        });
    }

    setupSearch(searchUnassigned, unassigned);
    setupSearch(searchAssigned, assigned);

    // initial sort
    sortLists();
});