/*! Minimalist Web Notepad | https://github.com/pereorga/minimalist-web-notepad */

// polyfill
AbortSignal.timeout ??= function timeout(ms) {
  const ctrl = new AbortController()
  setTimeout(() => ctrl.abort(), ms)
  return ctrl.signal
}

const uploadContent = async (data) => {
    const temp = textarea.value;
    try {
        const resp = await fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: data,
            signal: AbortSignal.timeout(6000)
        })
        if (!resp.ok) {
            throw new Error(`${resp.status} ${resp.statusText}`)
        } else {
            const titleArr = document.title.split('|');
            if (titleArr.length > 1) {
                document.title = titleArr[0];
                document.body.classList.remove('warning');
            }
            onServer = temp;
            document.body.classList.add('flash')
        }
    } catch (error) {
        const titleArr = document.title.split('|');
        if (titleArr.length = 1) {
            document.title = titleArr[0] + '| !!NETWORK ERROR!!';
            document.body.classList.add('warning');
        }
        console.error('Error:', error.message);
    }
}

const textarea = document.getElementById('content');
const printable = document.getElementById('printable');
const debounce = (callback, wait) => {
    let timeoutId = null;
    return (...args) => {
        window.clearTimeout(timeoutId);
        timeoutId = window.setTimeout(() => {
            callback(...args);
        }, wait);
    };
}
const updateTrigger = debounce(async () => {
    if (onServer === textarea.value) return;
    document.body.classList.remove('flash');
    await uploadContent(new URLSearchParams({
        'text': textarea.value
    }))
}, 2000);
const toggleView = () => {
    if (!window.markdownit) return;
    if (!md) md = window.markdownit();
    const isPrintView = textarea.classList.toggle('hide');
    if (isPrintView) printable.innerHTML = md.render(textarea.value);
    printable.classList.toggle('hide');
}
const updateContent = async () => {
    try {
        let searchParams = new URLSearchParams(window.location.search);
        searchParams.set('raw', '1');
        const resp = await fetch(window.location.pathname + '?' + searchParams.toString(), {
            method: 'GET',
            signal: AbortSignal.timeout(6000)
        })
        if (!resp.ok) {
            throw new Error(`${resp.status} ${resp.statusText}`)
        } else {
            onServer = await resp.text();
            if (onServer != textarea.value) {
                document.body.classList.add('warning');
            } else {
                document.body.classList.remove('warning');
            }
        }
    } catch (error) {
        document.body.classList.add('warning');
        console.error('Error:', error.message);
    }
}
var onServer = textarea.value;
var md = null;

// parse hash string
if (window.location.hash == "#md") toggleView();

// parse query string
// const params = new Proxy(new URLSearchParams(window.location.search), {
//   get: (searchParams, prop) => searchParams.get(prop),
// });
setInterval(updateContent, 20000);

// bind textarea update event
textarea.addEventListener('input', updateTrigger);

// bind Esc keypress event
document.addEventListener('keyup', function(e) {
    if (e.key === 'Escape') toggleView();
})

textarea.focus();
console.info(`链接参数说明：
[get]
#md: 进入markdown预览模式
?note: 文件名（值为[dir]则显示文件列表）
?signature: 值为[1]则返回限时链接
    ?expires: 限时链接过期天数（默认值[1]）
    ?readonly: 限时链接禁止编辑内容
[post]
?text: 更新内容
?append: 追加内容
`)
