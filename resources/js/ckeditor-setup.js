import {
    ClassicEditor,
    Essentials,
    Autoformat,
    BlockQuote,
    Bold,
    Italic,
    Underline,
    Strikethrough,
    Subscript,
    Superscript,
    Code,
    CodeBlock,
    Heading,
    Indent,
    IndentBlock,
    Link,
    List,
    ListProperties,
    TodoList,
    Paragraph,
    PasteFromOffice,
    Table,
    TableToolbar,
    TableProperties,
    TableCellProperties,
    TableColumnResize,
    Alignment,
    FontFamily,
    FontSize,
    FontColor,
    FontBackgroundColor,
    Highlight,
    RemoveFormat,
    HorizontalLine,
    SpecialCharacters,
    SpecialCharactersEssentials,
    SourceEditing,
    FindAndReplace,
    SelectAll,
    ShowBlocks,
    Fullscreen,
    WordCount,
    GeneralHtmlSupport,
} from 'ckeditor5';
import 'ckeditor5/ckeditor5.css';

// Diekspos lewat window karena skrip inline di _form.blade.php (bukan modul
// Vite) yang benar-benar membuat instance editor per baris uraian secara
// dinamis. Modul ES (dimuat via @vite) selalu async, jadi skrip inline
// menunggu event ini alih-alih mengandalkan window.ClassicEditor tersedia
// secara sinkron seperti versi CDN sebelumnya.
window.CKEditorBundle = {
    ClassicEditor,
    plugins: [
        Essentials, Autoformat, BlockQuote, Bold, Italic, Underline, Strikethrough,
        Subscript, Superscript, Code, CodeBlock, Heading, Indent, IndentBlock, Link,
        List, ListProperties, TodoList, Paragraph, PasteFromOffice, Table, TableToolbar,
        TableProperties, TableCellProperties, TableColumnResize, Alignment, FontFamily,
        FontSize, FontColor, FontBackgroundColor, Highlight, RemoveFormat, HorizontalLine,
        SpecialCharacters, SpecialCharactersEssentials, SourceEditing, FindAndReplace,
        SelectAll, ShowBlocks, Fullscreen, WordCount, GeneralHtmlSupport,
    ],
};
window.dispatchEvent(new Event('ckeditor5:ready'));
