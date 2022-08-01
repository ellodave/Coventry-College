<script>
function filterSubjects() {
    var input, filter, ul, li, a, i, txtValue;
    input = document.getElementById("filter-subjects");
    filter = input.value.toUpperCase();
    ul = document.getElementById("subject-list");
    li = document.getElementsByClassName("subject-list__item");
    for (i = 0; i < li.length; i++) {
        a = li[i].getElementsByTagName("h3")[0];
        txtValue = a.textContent || a.innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
            li[i].style.display = "";
        } else {
            li[i].style.display = "none";
        }
    }
}
</script>
