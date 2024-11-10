<?php
/**
 * HasNoteTrait trait
 */

namespace Marsender\EPubLoader\Models;

use Marsender\EPubLoader\CalibreDbLoader;

/**
 * Deal with note property
 */
trait HasNoteTrait
{
    /** @var NoteInfo|false|null */
    public $note = null;

    /**
     * Summary of getNote
     * @param ?CalibreDbLoader $loader
     * @return NoteInfo|false|null
     */
    public function getNote($loader = null)
    {
        if (isset($this->note)) {
            return $this->note;
        }
        if (empty($loader)) {
            return null;
        }
        $notes = $loader->getNotesDoc(static::$notesColName, [ (int) $this->id ]);
        if (empty($notes) || empty($notes[$this->id])) {
            $this->note = false;
            return $this->note;
        }
        return $this->setNote($notes[$this->id], $loader);
    }

    /**
     * Summary of setNote
     * @param array<mixed> $info
     * @param ?CalibreDbLoader $loader
     * @return NoteInfo
     */
    public function setNote($info, $loader = null)
    {
        $this->note = NoteInfo::load($this->basePath, $info, $loader);
        return $this->note;
    }

    /**
     * Summary of addNote
     * @param string $description
     * @param ?CalibreDbLoader $loader
     * @return NoteInfo
     */
    public function addNote($description, $loader = null)
    {
        $info = [
            'id' => '',
            'colname' => static::$notesColName,
            'item' => $this->id,
            'doc' => $description,
            'mtime' => time(),
        ];
        return $this->setNote($info, $loader);
    }
}
